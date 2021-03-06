<?php
//    Pastèque Web back office, Customers module
//
//    Copyright (C) 2013 Scil (http://scil.coop)
//
//    This file is part of Pastèque.
//
//    Pastèque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pastèque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pastèque.  If not, see <http://www.gnu.org/licenses/>.

namespace BaseCustomers;

$srv = new \Pasteque\CustomersService();
if (isset($_GET['delete-customer'])) {
    $srv->delete($_GET['delete-customer']);
}

$customers = $srv->getAll(true);
?>
<h1><?php \pi18n("Customers", PLUGIN_NAME); ?></h1>

<p><a class="btn" href="<?php echo \Pasteque\get_module_url_action(PLUGIN_NAME, 'customer_edit'); ?>"><img src="<?php echo \Pasteque\get_template_url(); ?>img/btn_add.png" /><?php \pi18n("Add a customer", PLUGIN_NAME); ?></a></p>

<p><?php \pi18n("%d customers", PLUGIN_NAME, count($customers)); ?></p>

<table cellspacing="0" cellpadding="0">
	<thead>
		<tr>
			<th><?php \pi18n("Customer.number"); ?></th>
			<th><?php \pi18n("Customer.key"); ?></th>
			<th><?php \pi18n("Customer.dispName"); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
<?php
foreach ($customers as $cust) {
?>
	<tr>
		<td><?php echo $cust->number; ?></td>
		<td><?php echo $cust->key; ?></td>
		<td><?php echo $cust->dispName; ?></td>
		<td class="edition">
                    <?php \Pasteque\tpl_btn('btn-edition', \Pasteque\get_module_url_action(
                            PLUGIN_NAME, 'customer_edit', array("id" => $cust->id)), "",
                            'img/edit.png', \i18n('Edit'), \i18n('Edit'));
                    ?>
                    <?php \Pasteque\tpl_btn('btn-delete', \Pasteque\get_current_url() . "&delete-customer=" . $cust->id, "",
                            'img/delete.png', \i18n('Delete'), \i18n('Delete'), true);
                    ?>
		</td>
	</tr>
<?php
}
?>
	</tbody>
</table>
