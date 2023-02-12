<?php
      if ( ! defined(WP_UNINSTALL_PLUGIN) ) return;

      global $wpdb;
      $table = $wpdb->prefix.'matching_history';
      $wpdb->query("DELETE TABLE IF EXISTS { $table}");