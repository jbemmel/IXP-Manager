<?php

/*
 * Copyright (C) 2009 - 2019 Internet Neutral Exchange Association Company Limited By Guarantee.
 * All Rights Reserved.
 *
 * This file is part of IXP Manager.
 *
 * IXP Manager is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, version v2.0 of the License.
 *
 * IXP Manager is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GpNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License v2.0
 * along with IXP Manager.  If not, see:
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Repositories;

use Doctrine\ORM\EntityRepository;

use Entities\{
    Infrastructure  as InfrastructureEntity,
    Router          as RouterEntity,
    Vlan            as VlanEntity,
    VlanInterface   as VlanInterfaceEntity
};


use Cache;

/**
 * Vlan
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Vlan extends EntityRepository
{
    /**
     * The cache key for all VLAN objects
     * @var string The cache key for all VLAN objects
     */
    const ALL_CACHE_KEY = 'inex_vlans';


    /**
     * Constant to represent normal and private VLANs
     * @var int Constant to represent normal and private VLANs
     */
    const TYPE_ALL     = 0;

    /**
     * Constant to represent normal VLANs only
     * @var int Constant to represent normal VLANs ony
     */
    const TYPE_NORMAL  = 1;

    /**
     * Constant to represent private VLANs only
     * @var int Constant to represent private VLANs ony
     */
    const TYPE_PRIVATE = 2;


    /**
     * Return an array of all VLAN objects from the database with caching
     * (and with the option to specify types - returns normal (non-private)
     * VLANs by default.
     *
     * @param $type int The VLAN types to return (see TYPE_ constants).
     * @param $orderBy string Typical values: number, name
     * @param $cache bool Whether to use the cache or not
     * @return \Entities\Vlan[] An array of all VLAN objects
     */
    public function getAndCache( int $type = self::TYPE_NORMAL, string $orderBy = "number", bool $cache = true )
    {
        switch( $type )
        {
            case self::TYPE_ALL:
                $where = "";
                break;

            case self::TYPE_PRIVATE:
                $where = "WHERE v.private = 1";
                break;

            default:
                $where = "WHERE v.private = 0";
                $type = self::TYPE_NORMAL;        // because we never validated $type
                break;
        }

        return $this->getEntityManager()->createQuery(
                "SELECT v FROM Entities\\Vlan v {$where} ORDER BY v.{$orderBy} ASC"
            )
            ->useResultCache( $cache, 3600, self::ALL_CACHE_KEY . "_{$type}_{$orderBy}" )
            ->getResult();
    }

    /**
     * Return an array of all VLAN names where the array key is the VLAN id (**not tag**).
     *
     * @param int           $type The VLAN types to return (see TYPE_ constants).
     * @param \Entities\IXP $ixp  IXP to filter vlan names
     * @return array An array of all VLAN names with the vlan id as the key.
     */
    public function getNames( $type = self::TYPE_NORMAL, $ixp = false )
    {
        $vlans = [];
        foreach( $this->getAndCache( $type ) as $a )
        {
            if( ( $ixp && $a->getInfrastructure()->getIXP() == $ixp ) || !$ixp )
                $vlans[ $a->getId() ] = $a->getName();
        }

        return $vlans;
    }

    /**
     * Return all active, trafficing and external VLAN interfaces on a given VLAN for a given protocol
     * (including customer details)
     *
     * Here's an example of the return:
     *
     *     array(56) {
     *         [0] => array(21) {
     *            ["ipv4enabled"] => bool(true)
     *            ["ipv4hostname"] => string(17) "inex.woodynet.net"
     *            ["ipv6enabled"] => bool(true)
     *            ["ipv6hostname"] => string(20) "inex-v6.woodynet.net"
     *            ....
     *            ["id"] => int(109)
     *                ["Vlan"] => array(5) {
     *                      ["name"] => string(15) "Peering VLAN #1"
     *                      ...
     *                }
     *                ["VirtualInterface"] => array(7) {
     *                    ["id"] => int(39)
     *                    ...
     *                }
     *                ["Customer"] => array(31) {
     *                    ["name"] => string(25) "Packet Clearing House DNS"
     *                   ...
     *                }
     *            }
     *         [1] => array(21) {
     *            ...
     *            }
     *        ...
     *     }
     *
     * @param int $vid The VLAN ID to find interfaces on
     * @param int $protocol The protocol to find interfaces on ( `4` or `6`)
     * @param bool $forceDb Set to true to ignore the cache and force the query to the database
     * @return array as described above
     * @throws \IXP_Exception Thrown if an invalid protocol is specified
     */
    public function getInterfaces( $vid, $protocol, $forceDb = false )
    {
        if( !in_array( $protocol, [ 4, 6 ] ) )
            throw new \IXP_Exception( 'Invalid protocol' );

        $interfaces = $this->getEntityManager()->createQuery(
                "SELECT vli, v, vi, c

                FROM \\Entities\\VlanInterface vli
                    LEFT JOIN vli.Vlan v
                    LEFT JOIN vli.VirtualInterface vi
                    LEFT JOIN vi.Customer c

                WHERE

                    " . Customer::DQL_CUST_CURRENT . "
                    AND " . Customer::DQL_CUST_TRAFFICING . "
                    AND " . Customer::DQL_CUST_EXTERNAL . "
                    AND v.id = ?1
                    AND vli.ipv{$protocol}enabled = 1

                ORDER BY c.autsys ASC"
            )
            ->setParameter( 1, $vid );

        if( !$forceDb ) {
            $interfaces->useResultCache( true, 3600 );
        }

        return $interfaces->getArrayResult();
    }

    /**
     * Return all active, trafficing and external customers on a given VLAN for a given protocol
     * (indexed by ASN)
     *
     * Here's an example of the return:
     *
     *     array(56) {
     *         [42] => array(5) {
     *             ["autsys"] => int(42)
     *             ["name"] => string(25) "Packet Clearing House DNS"
     *             ["shortname"] => string(10) "pchanycast"
     *             ["rsclient"] => bool(true)
     *             ["activepeeringmatrix"] => bool(true)
     *             ["custid"] => int(72)
     *         }
     *         [112] => array(5) {
     *             ["autsys"] => int(112)
     *             ...
     *         }
     *         ...
     *     }
     *
     * @see getInterfaces()
     * @param int $vid The VLAN ID to find interfaces on
     * @param int $protocol The protocol to find interfaces on ( `4` or `6`)
     * @param bool $forceDb Set to true to ignore the cache and force the query to the database
     * @return An array as described above
     * @throws \IXP_Exception Thrown if an invalid protocol is specified
     */
    public function getCustomers( $vid, $protocol, $forceDb = false )
    {
        $key = "vlan_customers_{$vid}_{$protocol}";

        if( !$forceDb && ( $custs = Cache::get( $key ) ) )
            return $custs;

        $acusts = $this->getInterfaces( $vid, $protocol, $forceDb );

        $custs = [];

        foreach( $acusts as $c )
        {
            $custs[ $c['VirtualInterface']['Customer']['autsys'] ] = [];
            $custs[ $c['VirtualInterface']['Customer']['autsys'] ]['autsys']              = $c['VirtualInterface']['Customer']['autsys'];
            $custs[ $c['VirtualInterface']['Customer']['autsys'] ]['name']                = $c['VirtualInterface']['Customer']['name'];
            $custs[ $c['VirtualInterface']['Customer']['autsys'] ]['shortname']           = $c['VirtualInterface']['Customer']['shortname'];
            $custs[ $c['VirtualInterface']['Customer']['autsys'] ]['rsclient']            = $c['rsclient'];
            $custs[ $c['VirtualInterface']['Customer']['autsys'] ]['activepeeringmatrix'] = $c['VirtualInterface']['Customer']['activepeeringmatrix'];
            $custs[ $c['VirtualInterface']['Customer']['autsys'] ]['custid']              = $c['VirtualInterface']['Customer']['id'];
        }

        Cache::put( $key, $custs, 86400 );

        return $custs;
    }

    /**
     * Find all VLANs marked for inclusion in the peering manager.
     *
     * @return VlanEntity[]
     */
    public function getPeeringManagerVLANs()
    {
        return $this->getEntityManager()->createQuery(
                "SELECT v FROM \\Entities\\Vlan v
                    WHERE
                        v.peering_manager = 1
                    ORDER BY v.number ASC"
            )
            ->getResult();
    }

    /**
    * Find all VLANs marked for inclusion in the peering matrices.
    *
    * @return VlanEntity[]
    */
    public function getPeeringMatrixVLANs()
    {
        $vlanEnts = $this->getEntityManager()->createQuery(
                "SELECT v FROM \\Entities\\Vlan v
                    WHERE v.peering_matrix = 1
                ORDER BY v.number ASC"
            )
            ->getResult();

        $vlans = [];

        foreach( $vlanEnts as $v ){
            $vlans[ $v->getId() ] = $v->getName();
        }

        return $vlans;
    }


    /**
     * Returns an array of private VLANs with their details and membership.
     *
     * A sample return would be:
     *
     *     [
     *         [8] => [             // vlanId
     *             [vlanid] => 8
     *             [name] => PV-BBnet-HEAnet
     *             [number] => 1300
     *             [members] => [
     *                 [764] => [            // cust ID
     *                     [id] => 764
     *                     [name] => CustA
     *                     [viid] => 169   // virtual interface ID
     *                 ]
     *                 [60] => [
     *                     [id] => 60
     *                     [name] => CustB
     *                     [viid] => 212
     *                 ]
     *             ],
     *             [locations] => [
     *                 [locationid] => location name
     *                 ...
     *             ],
     *             [switches] => [
     *                 [switchid] => switch name
     *                 ...
     *             ],
     *         ]
     *         [....]
     *         [....]
     *     ]
     *
     * @param InfrastructureEntity $infra
     * @return array
     */
    public function getPrivateVlanDetails( $infra = null )
    {
        $pvs = []; // private vlans
        /** @var VlanEntity $pv */
        foreach( $this->findBy( [ 'private' => 1 ] ) as $pv ) {

            if( $infra && $pv->getInfrastructure()->getId() != $infra->getId() ) {
                continue;
            }

            $pvs[ $pv->getId() ]['vlanid']         = $pv->getId();
            $pvs[ $pv->getId() ]['name']           = $pv->getName();
            $pvs[ $pv->getId() ]['number']         = $pv->getNumber();
            $pvs[ $pv->getId() ]['infrastructure'] = $pv->getInfrastructure()->getName();

            $members   = [];
            $locations = [];
            $switches  = [];

            foreach( $pv->getVlanInterfaces() as $vli ) {

                $custid = $vli->getVirtualInterface()->getCustomer()->getId();

                if( !isset( $members[ $custid ] ) ) {
                    $members[ $custid ]['id']     = $custid;
                    $members[ $custid ]['name']   = $vli->getVirtualInterface()->getCustomer()->getName();
                    $members[ $custid ]['viid']   = $vli->getVirtualInterface()->getId();
                }

                foreach( $vli->getVirtualInterface()->getPhysicalInterfaces() as $pi ) {
                    if( !isset( $locations[ $pi->getSwitchPort()->getSwitcher()->getCabinet()->getLocation()->getId() ] ) ) {
                        $locations[ $pi->getSwitchPort()->getSwitcher()->getCabinet()->getLocation()->getId() ] = $pi->getSwitchPort()->getSwitcher()->getCabinet()->getLocation()->getName();
                    }
                    if( !isset( $switches[ $pi->getSwitchPort()->getSwitcher()->getId() ] ) ) {
                        $switches[ $pi->getSwitchPort()->getSwitcher()->getId() ] = $pi->getSwitchPort()->getSwitcher()->getName();
                    }
                }
            }

            $pvs[ $pv->getId() ]['members']   = $members;
            $pvs[ $pv->getId() ]['locations'] = $locations;
            $pvs[ $pv->getId() ]['switches']  = $switches;
        }

        return $pvs;
    }


    /**
     * Utility function to provide an array of all VLAN interface IP addresses
     * and hostnames on a given VLAN for a given protocol for the purpose of generating
     * an ARPA DNS zone.
     *
     * Returns an array of elements such as:
     *
     *     [
     *         [enabled]  => 1/0
     *         [hostname] => ixp.rtr.example.com
     *         [address]  => 192.0.2.0 / 2001:db8:67::56f
     *     ]
     *
     * @param \Entities\Vlan $vlan The VLAN
     * @param int $proto Either 4 or 6
     * @param bool $useResultCache If true, use Doctrine's result cache (ttl set to one hour)
     * @return array As defined above.
     * @throws \IXP_Exception On bad / no protocol
     */
    public function getArpaDetails( $vlan, $proto, $useResultCache = true )
    {
        if( !in_array( $proto, [ 4, 6 ] ) ) {
            throw new \IXP_Exception( 'Invalid protocol specified' );
        }

        $qstr = sprintf( "SELECT vli.ipv{$proto}enabled AS enabled, addr.address AS address,
                                vli.ipv{$proto}hostname AS hostname,
                                %s( addr.address )%s AS aton
                          FROM Entities\\VlanInterface vli
                                JOIN vli.IPv{$proto}Address addr
                                JOIN vli.Vlan v
                            WHERE
                                v = :vlan
                                AND vli.ipv{$proto}hostname IS NOT NULL
                                AND vli.ipv{$proto}hostname != ''",
            $proto == 4 ? 'INET_ATON' : 'HEX(INET6_ATON', $proto == 4 ? '' : ')'
        );

        $qstr .= " ORDER BY aton ASC";

        $q = $this->getEntityManager()->createQuery( $qstr );
        $q->setParameter( 'vlan', $vlan );
        $q->useResultCache( $useResultCache, 3600 );
        return $q->getArrayResult();
    }



    /**
     * Get the IPv4 or IPv6 list for a vlan as an array.
     *
     * Returns a array sorted by IP address with elements:
     *
     *     {
     *         id: "1040",                     // address ID from the IPv4/6 table
     *         address: "2001:7f8:18::20",     // address
     *         v_id: "2",                      // VLAN id
     *         vli_id: "16"                    // VlanInterface ID (or null if not assigned / in use)
     *     },
     *
     * @param  int $vlan  The ID of the VLAN to query
     * $param  int $proto The IP protocol to get addresses for (one of RouterEntity::PROTOCOL_IPV4/6)
     * @return array Array of addresses as defined above.
     * @throws \Exception Only of an invalid protocol is provided
     */
    public function getIPAddresses( int $vlan, int $proto ) : array {


        if( $proto == RouterEntity::PROTOCOL_IPV6 ) {
            $af = 'ipv6'; $table = 'ipv6address';
        } else if( $proto == RouterEntity::PROTOCOL_IPV4 ) {
            $af = 'ipv4'; $table = 'ipv4address';
        } else {
            throw new \Exception('Invalid protocol' );
        }

        // going to use a native query so we can use INET[6]_ATON

        $conn = $this->getEntityManager()->getConnection();

        $stmt = $conn->prepare( "SELECT {$af}.id AS id, {$af}.address AS address, v.id AS v_id, vli.id as vli_id
                    FROM {$table} AS {$af}
                        LEFT JOIN vlan AS v ON {$af}.vlanid = v.id
                        LEFT JOIN vlaninterface AS vli ON {$af}.id = vli.{$af}addressid
                    WHERE
                        v.id = ? 
                    ORDER BY "
                        . ( $proto == RouterEntity::PROTOCOL_IPV4 ? 'INET_ATON( ' : 'INET6_ATON( ' )
                        . "address ) ASC"
        );

        $stmt->bindValue( 1, $vlan );
        $stmt->execute();

        return $stmt->fetchAll( \PDO::FETCH_ASSOC );
    }

    /**
     * Determine is an IP address /really/ free by checking across all vlans
     *
     * Returns a array of objects as follows (or empty array if not user):
     *
     * [
     *      [
     *          customer: {
     *              id: x,
     *              name: "",
     *              autsys: x,
     *              abbreviated_name: ""
     *          },
     *          virtualinterface: {
     *              id: x
     *          },
     *          vlaninterface: {
     *              id: x
     *          },
     *          vlan: {
     *              id: x,
     *              name: "",
     *              number: x
     *          }
     *      },
     *      {
     *
     *      ]
     * ]
     *
     * @param  string $ip The IPv6/4 address to check
     * @return array Array of object
     */
    public function usedAcrossVlans( string $ip ) : array
    {
        if( strpos( $ip, ':' ) !== false ) {
            $table = 'IPv6Address';
        } else {
            $table = 'IPv4Address';
        }

        $vlis = [];
        $result = $this->getEntityManager()->createQuery(
            "SELECT vli
                    FROM Entities\VlanInterface vli
                    LEFT JOIN vli.{$table} ip
                    WHERE ip.address = ?1"
            )
            ->setParameter( 1, $ip );

        /** @var VlanInterfaceEntity $vli */
        foreach( $result->getResult() as $vli ){

            $vlis[] = (object)[
                'customer'          => [
                        'id'                    => $vli->getVirtualInterface()->getCustomer()->getId(),
                        'name'                  => $vli->getVirtualInterface()->getCustomer()->getName(),
                        'autsys'                => $vli->getVirtualInterface()->getCustomer()->getAutsys(),
                        'abbreviated_name'      => $vli->getVirtualInterface()->getCustomer()->getAbbreviatedName(),
                ],

                'virtualinterface'  => [
                        'id'                    => $vli->getVirtualInterface()->getId(),
                ],

                'vlaninterface'     => [
                        'id'                    => $vli->getId(),
                ],

                'vlan'              => [
                        'id'                    => $vli->getVlan()->getId(),
                        'name'                  => $vli->getVlan()->getName(),
                        'number'                => $vli->getVlan()->getNumber(),
                ],

            ];

        }

        return $vlis;
    }

    /**
     * Get all vlans (or a particular one) for listing on the frontend CRUD
     *
     * @see \IXP\Http\Controllers\Doctrine2Frontend
     *
     *
     * @param \stdClass $feParams
     * @param int|null $id
     * @return array Array of vlans (as associated arrays) (or single element if `$id` passed)
     */
    public function getAllForFeList( \stdClass $feParams, int $id = null )
    {
        $dql = "SELECT  v.id AS id, 
                        v.name AS name, 
                        v.number AS number,
                        v.config_name AS config_name, 
                        v.notes AS notes,
                        v.private AS private, 
                        v.peering_matrix AS peering_matrix,
                        v.peering_manager AS peering_manager,
                        i.shortname AS infrastructure,
                        ix.shortname AS ixp
                        
                FROM Entities\\Vlan v
                    LEFT JOIN v.Infrastructure i
                    LEFT JOIN i.IXP ix
                    
                WHERE 1 = 1";

        if( $id ) {
            $dql .= " AND v.id = " . (int)$id;
        }

        if( isset( $feParams->privateList ) && $feParams->privateList ){
            $dql .= " AND v.private = 1";
        } else if( isset( $feParams->publicOnly ) && $feParams->publicOnly === true ) {
            $dql .= " AND v.private != 1";
        }

        if( isset( $feParams->infra) && $feParams->infra ){
            $dql .= " AND i.id = ".$feParams->infra->getId();
        }

        if( isset( $feParams->listOrderBy ) ) {
            $dql .= " ORDER BY " . $feParams->listOrderBy . ' ';
            $dql .= isset( $feParams->listOrderByDir ) ? $feParams->listOrderByDir : 'ASC';
        }

        $query = $this->getEntityManager()->createQuery( $dql );

        return $query->getArrayResult();
    }

    public function getInfraConfigNameCouple( $infraid, $configName ){

        $dql = "SELECT  v
                FROM Entities\\Vlan v
                WHERE v.Infrastructure = :infraid
                AND v.config_name = :configname";



        $q = $this->getEntityManager()->createQuery( $dql );

        $q->setParameter( 'infraid', $infraid );
        $q->setParameter( 'configname', $configName );

        return $q->getOneOrNullResult();
    }
}
