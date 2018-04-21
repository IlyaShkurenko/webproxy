<form action="" method="POST">

	{if $saved}
		<div class="successbox">
			<strong><span class="title">Changes Saved Successfully!</span></strong>
			<br>
			Configuration settings have been saved successfully
		</div>
		<hr>
	{/if}

	<div class="btn-container" style="text-align: left; padding: 0">
		<input class="btn btn-success" type="submit" value="Save">
	</div>
	<hr>

	<ul class="nav nav-tabs admin-tabs" role="tablist">
		<li class="active"><a href="#tiers" role="tab" data-toggle="tab" aria-expanded="false">Pricing Tiers</a></li>
		<li><a href="#settings" role="tab" data-toggle="tab" aria-expanded="false">Settings</a></li>
	</ul>

	<div class="tab-content admin-tabs">
		<div class="tab-pane active" id="tiers">
			{foreach from=$pricing item="pricingTable" key="id"}
				<h2>{$products[$id].name}</h2>

				<h3>Product/Service</h3>

				<div class="form-group">
					<select name="pricing[{$id}][productId]" id="">
						{foreach from=$productsGrouped item="productsInGroup" key="groupTitle"}
							<optgroup label="{$groupTitle}">
								{foreach from=$productsInGroup item="product"}
									<option value="{$product.id}" {if $id==$product.id}selected="selected"{/if}>{$product.name}</option>
								{/foreach}
							</optgroup>
						{/foreach}
					</select>
				</div>

				<h3>Type</h3>

				<div class="form-group">
					<select name="pricing[{$id}][type]" id="">
						{foreach from=$availableTypes item="title" key="type"}
							<option value='{$type}' {if $type==$pricingTable.0.type}selected="selected"{/if}>{$title}</option>
						{/foreach}
					</select>
				</div>

				<h3>Pricing tier</h3>

				<table class="table table-bordered">
					<tr>
						<th>From</th>
						<th>To</th>
						<th>Price</th>
						<th>Published</th>
					</tr>
					{foreach from=$pricingTable item="tier" key="i"}
						<tr>
							<td>
								<input type="text" class="form-control" size="3" name="pricing[{$id}][tiers][{$i}][from]" value="{$tier.from}" />
							</td>
							<td>
								<input type="text" class="form-control" size="3" value="{if $tier.to}{$tier.to}{else}∞{/if}" disabled />
							</td>
							<td>
								<input type="text" class="form-control" size="3" name="pricing[{$id}][tiers][{$i}][price]" value="{$tier.price}" />
							</td>
							<td>
								<label class="checkbox-inline">
									<input type="checkbox" name="pricing[{$id}][tiers][{$i}][published]" value="1"
									       {if $tier.published}checked="checked"{/if}>
								</label>
							</td>
						</tr>
					{/foreach}
					{foreach from=range(101, 103) item="i"}
						<tr>
							<td>
								<input type="text" class="form-control" size="3" name="pricing[{$id}][tiers][{$i}][from]" value="" />
							</td>
							<td>
								<input type="text" class="form-control" size="3" disabled />
							</td>
							<td>
								<input type="text" class="form-control" size="3" name="pricing[{$id}][tiers][{$i}][price]" value="" />
							</td>
							<td>
								<label class="checkbox-inline">
									<input type="checkbox" name="pricing[{$id}][tiers][{$i}][published]" value="1" checked="checked">
								</label>
							</td>
						</tr>
					{/foreach}
				</table>

				<h3>Sort order</h3>

				<div class="form-group">
					<input type="text" name="pricing[{$id}][sortOrder]" value="{$pricingTable.0.sortOrder}" />
				</div>

				<hr>
			{/foreach}

			{* New Product *}

			<h2>New Pricing Table</h2>

			<h3>Product/Service</h3>

			<div class="form-group">
				<select name="pricing[new][productId]" id="">
					<option value="0">None</option>
					{foreach from=$productsGrouped item="productsInGroup" key="groupTitle"}
						<optgroup label="{$groupTitle}">
							{foreach from=$productsInGroup item="product"}
								<option value="{$product.id}">{$product.name}</option>
							{/foreach}
						</optgroup>
					{/foreach}
				</select>
			</div>

			<h3>Type</h3>

			<div class="form-group">
				<select name="pricing[new][type]" id="">
					<option value="0">None</option>
					{foreach from=$availableTypes item="title" key="type"}
						<option value="{$type}">{$title}</option>
					{/foreach}
				</select>
			</div>

			<h3>Pricing tier</h3>

			<table class="table table-bordered">
				<tr>
					<th>From</th>
					<th>To</th>
					<th>Price</th>
					<th>Published</th>
				</tr>
				{foreach from=range(1, 6) item="i"}
					<tr>
						<td>
							<input type="text" class="form-control" size="3" name="pricing[new][tiers][{$i}][from]" value="" />
						</td>
						<td>
							<input type="text" class="form-control" size="3" disabled />
						</td>
						<td>
							<input type="text" class="form-control" size="3" name="pricing[new][tiers][{$i}][price]" value="" />
						</td>
						<td>
							<label class="checkbox-inline">
								<input type="checkbox" name="pricing[new][tiers][{$i}][published]" value="1" checked="checked">
							</label>
						</td>
					</tr>
				{/foreach}
			</table>

			<h3>Sort order</h3>

			<div class="form-group">
				<input type="text" name="pricing[new][sortOrder]" value="{$pricing|count + 1}" />
			</div>

		</div>
		<div class="tab-pane" id="settings">
				<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
					<tbody>
					<tr>
						<td class="fieldlabel">Default Payment Method</td>
						<td class="fieldarea">
							<select name="settings[payment_gateway]" class="form-control select-inline">
								{foreach from=$availablePaymentMethods item="title" key="method"}
									<option value="{$method}" {if $method == $settings.payment_gateway}selected="selected"{/if}>{$title}</option>
								{/foreach}
							</select>
							<br>
							The default method which will be used by plugin. If something wrong with payment method -
							plugin will use first one
						</td>
					</tr>
					</tbody>
				</table>

		</div>
	</div>

	<div class="btn-container" style="text-align: left">
		<input class="btn btn-success" type="submit" value="Save">
	</div>
</form>