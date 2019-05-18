<?php

/**
 * @file
 * {@inheritdoc}
 */
?>
<style>
    html {
        height: 100%;
    }

    body {
        min-height: calc(100% - 80px);
        height: calc(100% - 80px);
        margin: 0;
    }

    .elfsight-portal-embed {
        border: none;
        height: calc(100% - 10px);
    }

    .layout-container,
    .content-header {
        display: none;
    }
</style>

<iframe class="elfsight-portal-embed" width="100%" height="100%" src="<?php echo $url ?>"></iframe>
