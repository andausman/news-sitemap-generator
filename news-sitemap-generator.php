<?php
/**
 * Plugin Name: News Sitemap Generator
 * Description: Generates a News XML sitemap for your News Publication
 * Version: 1.0
 * Author: Anda Usman
 * Author URI: https://andausman.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Helper function to safely escape XML content
function escape_html_for_xml($string) {
    return htmlspecialchars($string, ENT_XML1, 'UTF-8');
}

// Load plugin textdomain for translations
add_action('plugins_loaded', function() {
    load_plugin_textdomain('news-sitemap-generator', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// ------------------------
// 1. Rewrite Rule
// ------------------------
add_action('init', function () {
    add_rewrite_tag('%custom_news_sitemap%', '1');
    add_rewrite_rule('newsfeed\\.xml$', 'index.php?custom_news_sitemap=1', 'top');
});

// ------------------------
// 2. Parse Request for Custom Sitemap
// ------------------------
add_action('parse_request', function ($wp) {
    if (isset($wp->query_vars['custom_news_sitemap'])) {
        // Remove any existing output filters
        remove_all_filters('the_content');
        remove_all_filters('the_excerpt');
        // Clear any previous output
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/xml; charset=utf-8');
        echo generate_news_sitemap();
        exit;
    }
});

// ------------------------
// 3. Generate Sitemap XML
// ------------------------
function generate_news_sitemap() {
    ob_start();
    $selected_category = get_option('news_sitemap_category_filter');
    $query_args = [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 1000,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'date_query'     => [
            [
                'after' => '48 hours ago',
                'inclusive' => true,
            ],
        ],
    ];

    if ($selected_category && $selected_category !== 'all') {
        $query_args['category_name'] = $selected_category;
    }

    $cache_key = 'news_sitemap_cache_' . md5(json_encode($query_args));
    $cached = get_transient($cache_key);
    if ($cached) return $cached;

    $posts = get_posts($query_args);
    $publication_name = get_option('news_sitemap_publication_name', get_bloginfo('name'));

    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
    $sitemap .= 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">';

    foreach ($posts as $post) {
        $sitemap .= '<url>';
        $sitemap .= '<loc>' . esc_url(get_permalink($post)) . '</loc>';
        $sitemap .= '<lastmod>' . escape_html_for_xml(get_the_modified_date('c', $post)) . '</lastmod>';
        $sitemap .= '<news:news>';
        $sitemap .= '<news:publication>';
        $sitemap .= '<news:name>' . escape_html_for_xml($publication_name) . '</news:name>';
        $sitemap .= '<news:language>' . get_bloginfo('language') . '</news:language>';
        $sitemap .= '</news:publication>';
        $sitemap .= '<news:publication_date>' . escape_html_for_xml(get_the_date('c', $post)) . '</news:publication_date>';
        $sitemap .= '<news:title>' . escape_html_for_xml(get_the_title($post)) . '</news:title>';

        $author = get_the_author_meta('display_name', $post->post_author);
        if ($author) {
            $sitemap .= '<news:author>' . escape_html_for_xml($author) . '</news:author>';
        }

        $tags = wp_get_post_tags($post->ID, ['fields' => 'names']);
        if (!empty($tags)) {
            $sitemap .= '<news:keywords>' . escape_html_for_xml(implode(', ', $tags)) . '</news:keywords>';
        }

        $sitemap .= '<news:genres>Blog</news:genres>';
        $sitemap .= '</news:news>';
        $sitemap .= '</url>';
    }    $sitemap .= '</urlset>';

    $output = ob_get_clean();
    set_transient($cache_key, $sitemap, HOUR_IN_SECONDS);
    return $sitemap;
}

// ------------------------
// 4. Ping Google News on Post Publish
// ------------------------
add_action('publish_post', function ($post_id) {
    if (!get_option('news_sitemap_enable_ping', true)) return;
    $sitemap_url = home_url('/newsfeed.xml');
    $ping_url = "http://www.google.com/ping?sitemap=" . urlencode($sitemap_url);
    wp_remote_get($ping_url);
    $log = ABSPATH . '/news-sitemap-ping.log';
    $msg = date('Y-m-d H:i:s') . " - Pinged Google News: $ping_url\n";
    file_put_contents($log, $msg, FILE_APPEND);
}, 10, 1);

// ------------------------
// 5. Plugin Settings Page
// ------------------------
add_action('admin_menu', function () {
    add_options_page(__('News Sitemap', 'news-sitemap-generator'), __('News Sitemap', 'news-sitemap-generator'), 'manage_options', 'news-sitemap', 'news_sitemap_settings_page');
});

function news_sitemap_settings_page() {
    $ping_log = ABSPATH . '/news-sitemap-ping.log';
    $log_output = file_exists($ping_log) ? nl2br(esc_html(file_get_contents($ping_log))) : __('No log yet.', 'news-sitemap-generator');
    $sitemap_slug = 'newsfeed.xml';
    ?>
    <div class="wrap">
        <h1><?php _e('News Sitemap', 'news-sitemap-generator'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('news_sitemap_settings');
            do_settings_sections('news-sitemap');
            submit_button();
            ?>
        </form>
        <form method="post">
            <?php submit_button(__('Regenerate Sitemap', 'news-sitemap-generator'), 'secondary', 'regenerate_news_sitemap'); ?>
        </form>
        <form method="post">
            <?php submit_button(__('Clear Ping Log', 'news-sitemap-generator'), 'delete', 'clear_ping_log'); ?>
        </form>
        <h2><?php _e('Sitemap', 'news-sitemap-generator'); ?></h2>
        <p><a href="<?php echo esc_url(home_url("/$sitemap_slug")); ?>" target="_blank"><?php _e('View News Sitemap', 'news-sitemap-generator'); ?></a></p>
        <h2><?php _e('Ping Log', 'news-sitemap-generator'); ?></h2>
        <div style="background:#f9f9f9; padding:1em; border:1px solid #ccc; max-height:300px; overflow:auto;">
            <?php echo $log_output; ?>
        </div>
    </div>
    <?php
}

add_action('admin_init', function () {
    register_setting('news_sitemap_settings', 'news_sitemap_publication_name');
    register_setting('news_sitemap_settings', 'news_sitemap_enable_ping');
    register_setting('news_sitemap_settings', 'news_sitemap_category_filter');

    add_settings_section('news_sitemap_main', '', null, 'news-sitemap');

    add_settings_field(
        'news_sitemap_publication_name',
        __('Publication Name', 'news-sitemap-generator'),
        function () {
            $value = esc_attr(get_option('news_sitemap_publication_name', get_bloginfo('name')));
            echo "<input type='text' name='news_sitemap_publication_name' value='$value' class='regular-text' />";
        },
        'news-sitemap',
        'news_sitemap_main'
    );

    add_settings_field(
        'news_sitemap_enable_ping',
        __('Enable Google News Ping', 'news-sitemap-generator'),
        function () {
            $checked = checked(get_option('news_sitemap_enable_ping', true), true, false);
            echo "<input type='checkbox' name='news_sitemap_enable_ping' value='1' $checked />";
        },
        'news-sitemap',
        'news_sitemap_main'
    );

    add_settings_field(
        'news_sitemap_category_filter',
        __('Limit to Category (optional)', 'news-sitemap-generator'),
        function () {
            $selected = get_option('news_sitemap_category_filter');
            $categories = get_categories(['hide_empty' => false]);
            echo "<select name='news_sitemap_category_filter'>";
            echo "<option value='all'" . selected($selected, 'all', false) . ">" . __('All Categories', 'news-sitemap-generator') . "</option>";
            foreach ($categories as $cat) {
                echo "<option value='{$cat->slug}'" . selected($selected, $cat->slug, false) . ">" . esc_html($cat->name) . "</option>";
            }
            echo "</select>";
        },
        'news-sitemap',
        'news_sitemap_main'
    );

    if (isset($_POST['clear_ping_log'])) {
        $log_file = ABSPATH . '/news-sitemap-ping.log';
        if (file_exists($log_file)) {
            unlink($log_file);
        }
        add_action('admin_notices', function () {
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html(__('Ping log cleared.', 'news-sitemap-generator')) . '</p></div>';
        });
    }
});

// ------------------------
// 6. Manual Sitemap Regeneration Trigger
// ------------------------
add_action('admin_init', function () {
    if (isset($_POST['regenerate_news_sitemap'])) {
        $selected_category = get_option('news_sitemap_category_filter');
        $query_args = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1000,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        if ($selected_category && $selected_category !== 'all') {
            $query_args['category_name'] = $selected_category;
        }
        $cache_key = 'news_sitemap_cache_' . md5(json_encode($query_args));
        delete_transient($cache_key);
        wp_remote_get(home_url('/newsfeed.xml'));
        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(__('News sitemap regenerated successfully.', 'news-sitemap-generator')) . '</p></div>';
        });
    }
});

// ------------------------
// 7. Flush rewrite rules on activation
// ------------------------
register_activation_hook(__FILE__, function () {
    flush_rewrite_rules();
});