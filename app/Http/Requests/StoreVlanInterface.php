<?php

namespace IXP\Http\Requests;

/*
 * Copyright (C) 2009-2017 Internet Neutral Exchange Association Company Limited By Guarantee.
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
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License v2.0
 * along with IXP Manager.  If not, see:
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

use Auth;

use Illuminate\Foundation\Http\FormRequest;


class StoreVlanInterface extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // middleware ensures superuser access only so always authorised here:
        return Auth::getUser()->isSuperUser();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'vlan'                  => 'required|integer',
            'maxbgpprefix'          => 'integer',
            'ipv4-address'          => 'ipv4' . ( $this->input('ipv4-enabled') ? '|required' : '|nullable' ),
            'ipv4canping'           => 'boolean',
            'ipv4monitorrcbgp'      => 'boolean',
            'ipv6-address'          => 'ipv6' . ( $this->input('ipv6-enabled') ? '|required' : '|nullable' ),
            'ipv6canping'           => 'boolean',
            'ipv6monitorrcbgp'      => 'boolean',
        ];
    }
}