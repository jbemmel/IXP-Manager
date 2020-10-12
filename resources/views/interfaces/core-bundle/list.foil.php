<?php
/** @var Foil\Template\Template $t */
$this->layout( 'layouts/ixpv4' );
?>

<?php $this->section( 'page-header-preamble' ) ?>
    Core Bundle / List
<?php $this->append() ?>

<?php $this->section( 'page-header-postamble' ) ?>
    <div class=" btn-group btn-group-sm" role="group">
        <button id="add-cb" type="button" class="btn btn-white dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-plus"></i> <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
            <a id="add-cb-wizard" class="dropdown-item" href="<?= route( 'core-bundle@create-wizard' )?>" >
                Create Core Bundle Wizard...
            </a>
        </ul>
    </div>
<?php $this->append() ?>

<?php $this->section('content') ?>
    <div class="row">
        <div class="col-sm-12">
            <?= $t->alerts() ?>
            <table id='table-cb' class="table collapse table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>
                            Description
                        </th>
                        <th>
                            Type
                        </th>
                        <th>
                            Enabled
                        </th>
                        <th>
                            Switch A
                        </th>
                        <th>
                            Switch B
                        </th>
                        <th>
                            Capacity
                        </th>
                        <th>
                            Action
                        </th>
                    </tr>
                <thead>
                <tbody>
                    <?php foreach( $t->cbs as $cb ): ?>
                        <tr>
                            <td>
                                <?= $t->ee( $cb->description )  ?>
                            </td>
                            <td>
                                <?= $t->ee( $cb->typeText() )  ?>
                            </td>
                            <td>
                                <?php if( !$cb->enabled ):?>
                                    <i class="fa fa-remove"></i>
                                <?php elseif( $cb->enabled && $cb->allCoreLinksEnabled() ): ?>
                                    <i class="fa fa-check"></i>
                                <?php else:?>
                                    <span class="badge badge-warning"> <?= $cb->coreLinks()->active()->get()->count() ?> / <?= $cb->coreLinks->count() ?> </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $t->ee( $cb->switchSideX( true )->name )  ?>
                            </td>
                            <td>
                                <?= $t->ee( $cb->switchSideX( false )->name )  ?>
                            </td>
                            <td data-sort="<?= $cb->coreLinks()->count() * $cb->speedPi() ?>" >
                                <?= $t->scaleBits(  $cb->coreLinks()->count() * $cb->speedPi() * 1000000, 0 )  ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a id="edit-cb-<?=  $cb->id ?>" class="btn btn-white" href="<?= route( 'core-bundle@edit' , [ 'cb' => $cb->id ] ) ?>" title="Edit">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach;?>
                <tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-10 offset-sm-1">
            <p class="tw-italic">
                <br><br>
                <sup>*</sup> Operational means the number of enabled core links for which both sides had a SNMP IFace operational state of 'up' the last time the switch
                was polled (typically every 5 mins).
            </p>
        </div>
    </div>

<?php $this->append() ?>

<?php $this->section( 'scripts' ) ?>
    <?= $t->insert( 'interfaces/core-bundle/js/list' ); ?>
<?php $this->append() ?>