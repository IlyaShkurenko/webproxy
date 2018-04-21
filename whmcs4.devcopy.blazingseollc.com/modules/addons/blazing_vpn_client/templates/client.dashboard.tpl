<table class="table table-bordered table-striped table-hover text-center">
	<thead>
	<tr>
		<th class="text-center">Country</th>
		<th class="text-center">Region</th>
		<th class="text-center">City</th>
		<th class="text-center"></th>
	</tr>
	</thead>
	<tbody>
	{foreach from=$locations item=location}
		<tr>
			<td>{$location.country}</td>
			<td>{$location.region}</td>
			<td>{$location.city}</td>
			<td><a href="{$location.url}" class="btn btn-success" target="_blank">Download</a></td>
		</tr>
	{/foreach}
	</tbody>
</table>