<script>
	window.ioPluginData = {};
	window.ioPluginData.serverOrigin = (location.host.indexOf('localhost')==0?'':location.protocol)+'<?php print $insiteConfig['insiteUIServerBase']; ?>';
	window.ioPluginData.adminPath = '<?php print admin_url();?>';
</script>
<iframe id="insite_iframe" src="<?php echo $insiteConfig['insiteUIServerBase']; ?>/server/login?<?php echo $sso_token?>&next=<?php echo urlencode($insiteConfig['insiteUIServerBase'].$path.'?siteUrl='.get_site_url().'&siteId='.get_option('insite_site_id')); ?>" scrolling="no"></iframe>
