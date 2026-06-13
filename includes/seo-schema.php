<?php

if (!defined('ABSPATH')) {
    exit;
}

// ========================================
// JSON-LD Schema.org structured data with a Yoast-style @graph.
// ========================================

add_action('wp_head', 'seo_output_schema_jsonld', 99);

function cc_seo_enhancer_get_default_schema_image_url()
{
    return plugin_dir_url(__DIR__) . 'assets/image/default.png';
}

function seo_get_site_info()
{
    $site_name = get_bloginfo('name');
    $site_url  = home_url('/');

    // Prefer the image uploaded in the plugin settings.
    $org_logo = trim(get_option('seo_enhancer_org_logo'));

    // Fall back to the site icon when no plugin image is set.
    if (empty($org_logo)) {
        $site_icon_id = get_option('site_icon');
        if ($site_icon_id) {
            $org_logo = wp_get_attachment_url($site_icon_id);
        }
    }

    // Fall back to the WordPress mystery avatar.
    if (empty($org_logo)) {
        $org_logo = get_avatar_url(0, ['default' => 'mystery', 'size' => 512]);
    }

    $org_name = get_option('seo_enhancer_org_name');
    $org_url  = get_option('seo_enhancer_org_url');
    $is_personal = (get_option('seo_enhancer_is_personal', '0') === '1');

    // In personal mode, use only manually entered publisher details.
    if ($is_personal) {
        $org_name = $org_name ?: $site_name;
    }

    return [
        'site_name'   => $site_name,
        'site_url'    => $site_url,
        'org_name'    => $org_name ?: $site_name,
        'org_url'     => $org_url ?: $site_url,
        'org_logo'    => $org_logo,
        'is_personal' => $is_personal,
    ];
}

// Shared author metadata.
if (!function_exists('seo_get_author_socials')) :
function seo_get_author_socials()
{
    // Try to determine the current author ID.
    $author_id = 0;
    if (is_author()) {
        $author = get_queried_object();
        $author_id = $author->ID ?? 0;
    } elseif (is_singular()) {
        global $post;
        $author_id = isset($post->post_author) ? (int)$post->post_author : 0;
    }

    if (!$author_id) return [];

    // Basic social profile fields.
    $socials = array_filter([
        get_the_author_meta('user_url', $author_id),
        get_the_author_meta('facebook', $author_id),
        get_the_author_meta('twitter', $author_id),
        get_the_author_meta('linkedin', $author_id),
    ]);

    // Add the publisher when it has the same name but a different ID.
    $org_name = get_option('seo_enhancer_org_name');
    $author_name = get_the_author_meta('display_name', $author_id);
    $author_url  = get_author_posts_url($author_id);
    $author_node_id = rtrim($author_url, '/') . '#person';
    $publisher_id = (get_option('seo_enhancer_org_url') ?: home_url('/')) . '#publisher';

    if ($author_name === $org_name && $author_node_id !== $publisher_id) {
        $socials[] = $publisher_id;
    }

    return $socials;
}
endif;

/**
 * Generate a Schema.org BreadcrumbList JSON-LD structure.
 * Can be called from any template via seo_generate_breadcrumb_jsonld().
 * @return array
 */
function seo_generate_breadcrumb_jsonld() {
    global $post;
    $breadcrumbs   = [];
    $breadcrumb_id = '';
    $pos = 1;

    // Shared home item.
    $home_item = [
        "@type"    => "ListItem",
        "position" => $pos++,
        "name"     => __('Home', 'cc-seo-enhancer'),
        "item"     => home_url('/'),
    ];

    // Template-specific breadcrumb items.
    if (is_home() || is_front_page()) {
        $breadcrumbs[] = $home_item;
        $breadcrumb_base = home_url('/');
        $breadcrumb_id   = trailingslashit($breadcrumb_base) . '#breadcrumb';

    } elseif (is_singular()) {
        $breadcrumbs[] = $home_item;

        $post_type = get_post_type($post);
        $taxonomy_map = [
            'post'    => 'category',
            'product' => 'product_cat',
            'course'  => 'course_category',
        ];
        $taxonomy = $taxonomy_map[$post_type] ?? null;

        if ($taxonomy) {
            $terms = get_the_terms($post->ID, $taxonomy);
            if ($terms && !is_wp_error($terms)) {
                $primary   = $terms[0];
                $ancestors = get_ancestors($primary->term_id, $taxonomy);
                foreach (array_reverse($ancestors) as $ancestor_id) {
                    $ancestor = get_term($ancestor_id, $taxonomy);
                    if ($ancestor && !is_wp_error($ancestor)) {
                        $breadcrumbs[] = [
                            "@type"    => "ListItem",
                            "position" => $pos++,
                            "name"     => $ancestor->name,
                            "item"     => get_term_link($ancestor)
                        ];
                    }
                }
                $breadcrumbs[] = [
                    "@type"    => "ListItem",
                    "position" => $pos++,
                    "name"     => $primary->name,
                    "item"     => get_term_link($primary)
                ];
            }
        }

        $breadcrumbs[] = [
            "@type"    => "ListItem",
            "position" => $pos++,
            "name"     => get_the_title(),
            "item"     => get_permalink()
        ];

        $breadcrumb_base = get_permalink($post);
        $breadcrumb_id   = trailingslashit($breadcrumb_base) . '#breadcrumb';

    } elseif (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        $breadcrumbs[] = $home_item;
        $ancestors = get_ancestors($term->term_id, $term->taxonomy);
        foreach (array_reverse($ancestors) as $ancestor_id) {
            $ancestor = get_term($ancestor_id, $term->taxonomy);
            if ($ancestor && !is_wp_error($ancestor)) {
                $breadcrumbs[] = [
                    "@type"    => "ListItem",
                    "position" => $pos++,
                    "name"     => $ancestor->name,
                    "item"     => get_term_link($ancestor)
                ];
            }
        }
        $breadcrumbs[] = [
            "@type"    => "ListItem",
            "position" => $pos++,
            "name"     => $term->name,
            "item"     => get_term_link($term)
        ];
        $breadcrumb_base = get_term_link($term);
        $breadcrumb_id   = trailingslashit($breadcrumb_base) . '#breadcrumb';

    } elseif (is_author()) {
        $author_obj = get_queried_object();
        $breadcrumbs[] = $home_item;
        $breadcrumbs[] = [
            "@type"    => "ListItem",
            "position" => $pos++,
            "name"     => $author_obj->display_name,
            "item"     => get_author_posts_url($author_obj->ID)
        ];
        $breadcrumb_base = get_author_posts_url($author_obj->ID);
        $breadcrumb_id   = trailingslashit($breadcrumb_base) . '#breadcrumb';

    } elseif (is_search()) {
        $breadcrumbs[] = $home_item;
        $breadcrumbs[] = [
            "@type"    => "ListItem",
            "position" => $pos++,
            /* translators: %s: Search query. */
            "name"     => sprintf(__('Search: %s', 'cc-seo-enhancer'), get_search_query()),
            "item"     => get_search_link()
        ];
        $breadcrumb_base = get_search_link();
        $breadcrumb_id   = trailingslashit($breadcrumb_base) . '#breadcrumb';

    } elseif (is_date()) {
        $breadcrumbs[] = $home_item;
        if (is_year()) {
            $breadcrumbs[] = [
                "@type"    => "ListItem",
                "position" => $pos++,
                /* translators: %s: Year. */
                "name"     => sprintf(__('%s year', 'cc-seo-enhancer'), get_query_var('year'))
            ];
        } elseif (is_month()) {
            $breadcrumbs[] = [
                "@type"    => "ListItem",
                "position" => $pos++,
                /* translators: %s: Year. */
                "name"     => sprintf(__('%s year', 'cc-seo-enhancer'), get_query_var('year')),
                "item"     => get_year_link(get_query_var('year'))
            ];
            $breadcrumbs[] = [
                "@type"    => "ListItem",
                "position" => $pos++,
                /* translators: %s: Month number. */
                "name"     => sprintf(__('%s month', 'cc-seo-enhancer'), get_query_var('monthnum'))
            ];
        } elseif (is_day()) {
            $year  = get_query_var('year');
            $month = get_query_var('monthnum');
            $day   = get_query_var('day');
            $breadcrumbs[] = [
                "@type"    => "ListItem",
                "position" => $pos++,
                /* translators: %s: Year. */
                "name"     => sprintf(__('%s year', 'cc-seo-enhancer'), $year),
                "item"     => get_year_link($year)
            ];
            $breadcrumbs[] = [
                "@type"    => "ListItem",
                "position" => $pos++,
                /* translators: %s: Month number. */
                "name"     => sprintf(__('%s month', 'cc-seo-enhancer'), $month),
                "item"     => get_month_link($year, $month)
            ];
            $breadcrumbs[] = [
                "@type"    => "ListItem",
                "position" => $pos++,
                /* translators: %s: Day number. */
                "name"     => sprintf(__('%s day', 'cc-seo-enhancer'), $day)
            ];
        }
        $breadcrumb_base = (is_day()
            ? get_day_link(get_query_var('year'), get_query_var('monthnum'), get_query_var('day'))
            : (is_month()
                ? get_month_link(get_query_var('year'), get_query_var('monthnum'))
                : get_year_link(get_query_var('year')))
        );
        $breadcrumb_id   = trailingslashit($breadcrumb_base) . '#breadcrumb';

    } elseif (is_404()) {
        $breadcrumbs[] = $home_item;
        $breadcrumbs[] = [
            "@type"    => "ListItem",
            "position" => $pos++,
            "name"     => __('404 not found', 'cc-seo-enhancer')
        ];
        $breadcrumb_base = home_url('/404/');
        $breadcrumb_id   = trailingslashit($breadcrumb_base) . '#breadcrumb';
    }

    if (!empty($breadcrumbs) && !empty($breadcrumb_id)) {
        return [
            "@type"           => "BreadcrumbList",
            "@id"             => $breadcrumb_id,
            "itemListElement" => $breadcrumbs
        ];
    }

    return null;
}

function seo_output_schema_jsonld()
{
    global $post;
    if ((int) get_option('enable_schema_output') !== 1) {
        return;
    }

    if (empty($post) && !is_tax() && !is_archive() && !is_author()) {
        return;
    }

    $site_info   = seo_get_site_info();
    $site_name   = $site_info['site_name'];
    $site_url    = $site_info['site_url'];
    $org_name    = $site_info['org_name'];
    $org_url     = $site_info['org_url'];
    $org_logo    = $site_info['org_logo'];
    $is_personal = $site_info['is_personal'];
    $schema_type = $is_personal ? 'Person' : 'Organization'; // Keep publisher semantics focused.

    $org_alternate_name  = get_option('seo_enhancer_org_alternate_name', '');

    $site_alternate_name = get_option('seo_enhancer_site_alternate_name', '');


    $graph = [];
    
    // --- 1) Publisher node ---
    $publisher_id = $org_url . '#publisher';
    $org_description = trim(get_option('seo_enhancer_org_description'));
    if (empty($org_description)) {
        $org_description = get_bloginfo('description'); // fallback
    }
   $publisher = [
    "@type" => $schema_type,
    "@id"   => $publisher_id,
    "name"  => $org_name,
    "alternateName" => $org_alternate_name ?: null,
    $is_personal ? "image" : "logo" => [
        "@type"  => "ImageObject",
        "url"    => $org_logo,
        "width"  => 512,
        "height" => 512,
        "caption" => wp_strip_all_tags($org_name),
        "inLanguage" => "zh-TW"
    ],
    "url"   => $org_url,
    "inLanguage" => "zh-TW"
  ];


  // Include description when provided.
  if (!empty($org_description)) {
      $publisher["description"] = wp_strip_all_tags($org_description);
  }

  // ------------------------------------
  // Allow a logo in personal mode.
  // ------------------------------------
  if ($is_personal) {
      $personal_logo = trim(get_option('seo_enhancer_personal_logo', ''));
      if (!empty($personal_logo)) {
          $publisher["logo"] = [
              "@type"  => "ImageObject",
              "url"    => esc_url($personal_logo),
              "width"  => 512,
              "height" => 512,
              "caption" => wp_strip_all_tags($org_name),
              "inLanguage" => "zh-TW"
          ];
      }
  }

// Add contact phone and address for organizations.
if (!$is_personal) {
    $email = trim(get_option('seo_enhancer_org_email', ''));

    if (!empty($email)) {
        $publisher["email"] = $email;
        $publisher["contactPoint"] = [[
            "@type" => "ContactPoint",
            "email" => $email,
            "contactType" => "Customer Service"
        ]];
    }
}

// Social profile links.
$socials = array_filter(array_map('trim', explode("\n", get_option('seo_enhancer_org_socials'))));
if (!empty($socials)) {
    $publisher["sameAs"] = $socials;
}

$graph[] = $publisher;

  // --- 3) WebSite node ---
  $website_id = $site_url . '#website';

  $graph[] = [
      "@type" => "WebSite",
      "@id"   => $website_id,
      "url"   => esc_url($site_url),
      "name"  => wp_strip_all_tags($site_name),
      "alternateName" => $site_alternate_name ?: null,
      "description" => wp_strip_all_tags(get_bloginfo('description')),
      "publisher" => [ "@id" => $publisher_id ],
      "potentialAction" => [[
          "@type" => "SearchAction",
          "target" => [
              "@type" => "EntryPoint",
              "urlTemplate" => $site_url . "?s={search_term_string}"
          ],
          "query-input" => [
              "@type" => "PropertyValueSpecification",
              "valueRequired" => true,
              "valueName" => "search_term_string"
          ]
      ]],
      "inLanguage" => "zh-TW"
  ];



$breadcrumb_graph = seo_generate_breadcrumb_jsonld();
if ($breadcrumb_graph) {
    $graph[] = $breadcrumb_graph;
}


    // ==========================================================
    // 5) Main content node with author.name and isPartOf.
    // ==========================================================
if (is_singular() && !is_page()) {
    global $post;

    $author_wp_id   = isset($post->post_author) ? (int) $post->post_author : 0;
    $author_name    = $author_wp_id ? get_the_author_meta('display_name', $author_wp_id) : $org_name;
    $author_url     = $author_wp_id ? get_author_posts_url($author_wp_id) : ($site_url . 'author/');
    $author_avatar  = get_avatar_url($author_wp_id, ['size' => 512]);
    $author_node_id = rtrim($author_url, '/') . '#person';

    // Get author social profile links.
    $author_socials = function_exists('seo_get_author_socials') ? seo_get_author_socials() : [];
    $author_socials = array_values(array_unique(array_filter((array)$author_socials)));

    // --- Person node ---
    $graph[] = array_filter([
        "@type" => "Person",
        "@id"   => $author_node_id,
        "name"  => $author_name,
        "alternateName" => $author_name, // Strengthen author identification.
        "url"   => $author_url,
        "image" => [
            "@type"  => "ImageObject",
            "url"    => $author_avatar,
            "width"  => 512,
            "height" => 512
        ],
        "description" => get_the_author_meta('description', $author_wp_id),
        "sameAs" => !empty($author_socials) ? $author_socials : null,
        "inLanguage" => "zh-TW"
    ]);

    $categories    = get_the_category($post->ID);
    $section_names = [];
    $genres        = [];

    if ($categories) {
        $primary   = $categories[0];
        $ancestors = get_ancestors($primary->term_id, 'category');
        foreach (array_reverse($ancestors) as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'category');
            if ($ancestor && !is_wp_error($ancestor)) {
                $section_names[] = $ancestor->name;
            }
        }
        $section_names[] = $primary->name;
        foreach ($categories as $cat) {
            $genres[] = $cat->name;
        }
    }

    $tags = get_the_tags($post->ID);
    $keywords = [];
    if ($tags) {
        foreach ($tags as $tag) {
            $keywords[] = $tag->name;
        }
    }

    $word_count    = cclin_seo_wordcount(wp_strip_all_tags($post->post_content));
    $comment_count = get_comments_number($post->ID);

    $permalink     = get_permalink();
    $webpage_id    = $permalink . '#webpage';
    $article_id    = $permalink . '#article';
    $primaryimg_id = $permalink . '#primaryimage';

    // --- WebPage node ---
    $graph[] = [
        "@type" => "WebPage",
        "@id"   => $webpage_id,
        "url"   => $permalink,
        "name"  => wp_strip_all_tags(get_the_title()) . ' – ' . get_bloginfo('name'), // Include the site name.
        "description" => wp_strip_all_tags(get_the_excerpt() ?: get_the_content()),
        "isPartOf" => [ "@id" => $website_id ],
        "breadcrumb" => [ "@id" => $permalink . '#breadcrumb' ],
        "primaryImageOfPage" => [ "@id" => $primaryimg_id ],
        "mainEntity" => [ "@id" => $article_id ],
        "dateModified" => get_the_modified_date('c'),
        "inLanguage" => "zh-TW",
        "potentialAction" => [[
            "@type" => "ReadAction",
            "target" => [ $permalink ]
        ]]
    ];

    // --- Article / BlogPosting node ---
    $img_url = esc_url(
        has_post_thumbnail() ? get_the_post_thumbnail_url($post, 'full')
                             : cc_seo_enhancer_get_default_schema_image_url()
    );

    $alt_headline = wp_strip_all_tags(
        get_post_meta($post->ID, 'subtitle', true)
        ?: get_post_meta($post->ID, 'alternative_headline', true)
    );

    $thumb_id = get_post_thumbnail_id($post->ID);
    $caption  = '';
    if ($thumb_id) {
        $thumb_post = get_post($thumb_id);
        if ($thumb_post && !is_wp_error($thumb_post)) {
            $caption = wp_strip_all_tags($thumb_post->post_excerpt ?? '');
        }
    }

    $graph[] = array_filter([
        "@type" => ["Article", "BlogPosting"], // Use both article types.
        "@id"   => $article_id,
        "isPartOf" => [ "@id" => $webpage_id ],
        "mainEntityOfPage" => $permalink, // Use the direct URL.
        "author" => [ "@id" => $author_node_id ],
        "headline" => wp_strip_all_tags(get_the_title()),
        "alternativeHeadline" => $alt_headline ?: null,
        "description" => wp_strip_all_tags(get_the_excerpt() ?: get_the_content()),
        "image" => array_filter([
            "@type" => "ImageObject",
            "@id" => $primaryimg_id,
            "url" => $img_url,
            "contentUrl" => $img_url,
            "width" => 1200,
            "height" => 675,
            "caption" => $caption ?: null
        ]),
        "thumbnailUrl" => $img_url, // Include the thumbnail URL.
        "publisher" => [ "@id" => $publisher_id ],
        "datePublished" => get_the_date('c'),
        "dateModified"  => get_the_modified_date('c'),
        "wordCount" => (int) $word_count,
        "commentCount" => (int) $comment_count,
        "articleSection" => $section_names,
        "genre" => $genres,
        "keywords" => $keywords,
        "inLanguage" => "zh-TW",
        "potentialAction" => [[
            "@type" => "CommentAction",
            "name" => "Comment",
            "target" => [ $permalink . '#respond' ]
        ]]
    ]);
    } elseif (is_page()) {
        $img_url = esc_url(
          has_post_thumbnail()
              ? get_the_post_thumbnail_url($post, 'full')
              : (
                  $org_logo
                      ? $org_logo
                      : cc_seo_enhancer_get_default_schema_image_url()
              )
      );

        $caption = wp_strip_all_tags(get_post(get_post_thumbnail_id())->post_excerpt ?? '');

        // Main WebPage node.
        $graph[] = [
            "@type" => "WebPage",
            "@id"   => get_permalink(),
            "url"   => get_permalink(),
            "isPartOf" => [ "@id" => $website_id ],
            "name"  => wp_strip_all_tags(get_the_title()) . ' - ' . get_bloginfo('name'),
            "description" => wp_strip_all_tags(get_the_excerpt() ?: get_the_content()),
            "breadcrumb" => [ "@id" => get_permalink() . '#breadcrumb' ],
            "primaryImageOfPage" => [ "@id" => get_permalink() . '#primaryimage' ],
            "image" => [ "@id" => get_permalink() . '#primaryimage' ],
            "thumbnailUrl" => $img_url,
            "mainEntityOfPage" => get_permalink(),
            "datePublished" => get_the_date('c'),
            "dateModified"  => get_the_modified_date('c'),
            "mainEntityOfPage" => [ "@id" => get_permalink() ],
            "inLanguage" => "zh-TW",
            "potentialAction" => [[
                "@type" => "ReadAction",
                "target" => [ get_permalink() ]
            ]],
            "about" => [
                "@type" => "Thing",
                "name"  => wp_strip_all_tags(get_the_title())
            ]
        ];

        // Standalone ImageObject node.
        $image_object = [
            "@type" => "ImageObject",
            "@id" => get_permalink() . '#primaryimage',
            "inLanguage" => "zh-TW",
            "url" => $img_url,
            "contentUrl" => $img_url,
            "width" => 1200,
            "height" => 675
        ];

        // Include caption only when available.
        if ($caption !== '') {
            $image_object["caption"] = $caption;
        }

        $graph[] = $image_object;

    } elseif (is_category() || is_tag() || is_tax()) {
    $term = get_queried_object();
    $term_url = get_term_link($term);

    // Primary image with fallback.
    $term_image = get_term_meta($term->term_id, 'thumbnail_id', true);
    $image_url = $term_image ? wp_get_attachment_url($term_image) : '';

    // If the term has no image, use the first related post with a featured image.
    if (empty($image_url)) {
        $taxonomy_obj = get_taxonomy($term->taxonomy);
        $post_types = $taxonomy_obj && !empty($taxonomy_obj->object_type)
            ? $taxonomy_obj->object_type
            : ['post']; // Fall back to posts.

        $term_posts = get_posts([
            'post_type' => $post_types,
            'tax_query' => [[
                'taxonomy' => $term->taxonomy,
                'field'    => 'term_id',
                'terms'    => $term->term_id,
            ]],
            'posts_per_page' => 1,
            'meta_query' => [[
                'key' => '_thumbnail_id',
                'compare' => 'EXISTS'
            ]]
        ]);

        if (!empty($term_posts)) {
            $image_url = get_the_post_thumbnail_url($term_posts[0]->ID, 'full');
        }
    }

// Fall back to the site logo or bundled default image.
if (empty($image_url)) {
    $image_url = $org_logo ?: cc_seo_enhancer_get_default_schema_image_url();
}

    // --- CollectionPage ---
    $graph[] = [
        "@type" => "CollectionPage",
        "@id"   => $term_url,
        "url"   => $term_url,
        "isPartOf" => [ "@id" => $website_id ],
        "about" => [ "@id" => $publisher_id ],
        "breadcrumb" => [ "@id" => $term_url . '#breadcrumb' ],
        "name" => $term->name . ' - ' . get_bloginfo('name'),
        "description" => wp_strip_all_tags(term_description($term)) ?: get_bloginfo('description'),
        "primaryImageOfPage" => $image_url ? [ "@id" => $term_url . '#primaryimage' ] : null,
        "mainEntityOfPage" => $term_url,
        "inLanguage" => "zh-TW"
    ];
    // Add an ImageObject when a term image is available.
      if ($image_url) {
          $graph[] = [
              "@type" => "ImageObject",
              "@id" => $term_url . '#primaryimage',
              "inLanguage" => "zh-TW",
              "url" => $image_url,
              "contentUrl" => $image_url,
              "width" => 1200,
              "height" => 675,
              "caption" => wp_strip_all_tags($term->name),
              "fileFormat" => "image/jpeg",
              "mainEntityOfPage" => $term_url
          ];
      }

   } elseif (is_author()) {
    $author_obj  = get_queried_object();
    $avatar_url  = get_avatar_url($author_obj->ID, ['size' => 512]);
    $author_page = get_author_posts_url($author_obj->ID);
    $author_name = $author_obj->display_name;
    $author_desc = get_the_author_meta('description', $author_obj->ID);
    $author_socials = seo_get_author_socials();

    // ProfilePage node.
    $graph[] = [
        "@type" => "ProfilePage",
        "@id"   => rtrim($author_page, '/') . '#webpage',
        "url"   => $author_page,
        /* translators: %s: Author display name. */
        "name"  => sprintf(__('%s - Author page', 'cc-seo-enhancer'), $author_name),
        "isPartOf" => [ "@id" => get_home_url() . '/#website' ],
        "about" => [ "@id" => rtrim($author_page, '/') . '#author' ],
        "breadcrumb" => [ "@id" => rtrim($author_page, '/') . '#breadcrumb' ],
        "inLanguage" => "zh-TW",
        "potentialAction" => [
            [
                "@type" => "ReadAction",
                "target" => [ $author_page ]
            ]
        ]
    ];

    // Person node.
    $graph[] = [
        "@type" => "Person",
        "@id"   => rtrim($author_page, '/') . '#author',
        "name"  => $author_name,
        "image" => [
            "@type" => "ImageObject",
            "url"   => $avatar_url
        ],
        "description" => $author_desc,
        "url" => $author_page,
        "sameAs" => $author_socials,
        "inLanguage" => "zh-TW"
    ];



   } elseif (is_home() || is_front_page()) {

    $graph[] = [
        "@type" => "CollectionPage",
        "@id"   => esc_url($site_url . '#home'),
        "url"   => esc_url($site_url),
        "isPartOf" => [ "@id" => $website_id ],
        "about" => [ "@id" => $publisher_id ],
        "name" => wp_strip_all_tags($site_alternate_name . ' - ' . ($site_name ?: get_bloginfo('description'))),
        "description" => wp_strip_all_tags(get_bloginfo('description')),
        "breadcrumb" => [ "@id" => esc_url($site_url . '#breadcrumb') ],
        "inLanguage" => "zh-TW"
    ];

    // --- Breadcrumb for Home ---
    $graph[] = [
        "@type" => "BreadcrumbList",
        "@id"   => esc_url($site_url . '#breadcrumb'),
        "itemListElement" => [[
            "@type" => "ListItem",
            "position" => 1,
            "name" => __('Home', 'cc-seo-enhancer')
        ]]
    ];
}





    // --- Output the combined JSON-LD graph ---
    $jsonld = [
        "@context" => "https://schema.org",
        "@graph"   => $graph
    ];
    echo "<script type=\"application/ld+json\">\n"
        . json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        . "\n</script>\n";
}
