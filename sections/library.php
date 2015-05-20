<script>
	window.ioPluginData = {};
	window.ioPluginData.blogPages = <?php print json_encode($pagesToClient); ?>;
	window.ioPluginData.serverOrigin = location.protocol + '<?php print $insiteConfig['insiteUIServerBase']; ?>';
	window.ioPluginData.adminPath = '<?php print admin_url();?>';
    window.ioPluginData.pluginVersion = '<?php print Insite_Admin::getPluginVersion(); ?>';
</script>
<iframe id="insite_iframe" src="<?php echo $insiteConfig['insiteUIServerBase']; ?>/server/login?<?php echo $sso_token?>&next=<?php echo urlencode($insiteConfig['insiteUIServerBase'].$path.'?pluginVersion=' . Insite_Admin::getPluginVersion() . '&siteUrl='.get_site_url().'&siteId='.get_option('insite_site_id')); ?>" scrolling="no"></iframe>
