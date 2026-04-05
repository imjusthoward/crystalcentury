<?php
/**
 * Hello Elementor Child — functions.php
 */

// ── Suppress WPML-Elementor E_WARNING spam (known compatibility noise) ────────
// WPML iterates Elementor data that can have null elements in some widget types;
// these warnings flood the error log but are non-fatal and produce no frontend impact.
set_error_handler( function ( $errno, $errstr, $errfile ) {
    if ( $errno === E_WARNING
        && strpos( $errfile, 'sitepress-multilingual-cms' ) !== false
        && strpos( $errfile, 'Elementor' ) !== false
    ) {
        return true;
    }
    return false;
}, E_WARNING );

remove_action( 'wp_head', 'wp_generator' );

// ── Stylesheets ──────────────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'hello-elementor-child',
        get_stylesheet_uri(),
        [ 'hello-elementor-style' ],
        wp_get_theme()->get( 'Version' )
    );
}, 20 );

function cc_current_lang() {
    if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE ) {
        return ICL_LANGUAGE_CODE;
    }

    if ( function_exists( 'apply_filters' ) ) {
        $wpml_lang = apply_filters( 'wpml_current_language', null );
        if ( is_string( $wpml_lang ) && $wpml_lang !== '' ) {
            return sanitize_key( $wpml_lang );
        }
    }

    $query_lang = sanitize_key( wp_unslash( $_GET['lang'] ?? '' ) );
    if ( $query_lang !== '' ) {
        return $query_lang;
    }

    return 'zh-hant';
}

function cc_is_en() {
    return cc_current_lang() === 'en';
}

function cc_localize_url( $url, $lang = '' ) {
    $lang = $lang ? sanitize_key( $lang ) : cc_current_lang();
    $url  = is_string( $url ) ? $url : '';

    if ( $url === '' ) {
        return '';
    }

    if ( function_exists( 'apply_filters' ) ) {
        $localized = apply_filters( 'wpml_permalink', $url, $lang, true );
        if ( is_string( $localized ) && $localized !== '' ) {
            $url = $localized;
        }
    }

    if ( $lang === 'en' && strpos( $url, 'lang=' ) === false ) {
        $url = add_query_arg( 'lang', 'en', $url );
    }

    return $url;
}

function cc_base_home_url( $path = '/' ) {
    $home   = home_url( '/' );
    $parts  = wp_parse_url( $home );
    $scheme = $parts['scheme'] ?? 'https';
    $host   = $parts['host'] ?? '';
    $port   = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
    $base   = isset( $parts['path'] ) ? rtrim( (string) $parts['path'], '/' ) : '';
    $home   = $scheme . '://' . $host . $port . $base;
    $path = '/' . ltrim( (string) $path, '/' );

    return $home . $path;
}

function cc_resolve_page_url( array $page_ids, array $path_candidates, $lang = '' ) {
    $lang = $lang ? sanitize_key( $lang ) : cc_current_lang();

    $candidate_ids = [];

    if ( isset( $page_ids[ $lang ] ) ) {
        $candidate_ids[] = (int) $page_ids[ $lang ];
    }

    if ( isset( $page_ids['default'] ) ) {
        $candidate_ids[] = (int) $page_ids['default'];
    }

    foreach ( array_unique( array_filter( $candidate_ids ) ) as $page_id ) {
        $translated_id = (int) apply_filters( 'wpml_object_id', $page_id, 'page', true, $lang );
        $page_url      = $translated_id ? get_permalink( $translated_id ) : '';

        if ( $page_url ) {
            return cc_localize_url( $page_url, $lang );
        }
    }

    foreach ( $path_candidates as $path_candidate ) {
        $path_candidate = trim( (string) $path_candidate, '/' );

        if ( ! $path_candidate ) {
            continue;
        }

        $page = get_page_by_path( $path_candidate );

        if ( $page instanceof WP_Post ) {
            return cc_localize_url( get_permalink( $page ), $lang );
        }
    }

    return cc_localize_url( home_url( '/' ), $lang );
}

function cc_contact_page_url( $lang = '' ) {
    return cc_resolve_page_url(
        [
            'zh-hant' => 38309,
            'en'      => 41116,
            'default' => 38309,
        ],
        [ 'contact-us-en', 'contact-us', '聯絡我們' ],
        $lang
    );
}

function cc_product_category_url( $term_id, $lang = '' ) {
    $lang          = $lang ? sanitize_key( $lang ) : cc_current_lang();
    $translated_id = (int) apply_filters( 'wpml_object_id', (int) $term_id, 'product_cat', true, $lang );
    $term_link     = $translated_id ? get_term_link( $translated_id, 'product_cat' ) : '';

    if ( ! is_wp_error( $term_link ) && $term_link ) {
        return $term_link;
    }

    return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
}

function cc_brand_name() {
    return cc_is_en() ? 'Premier Trophy' : '卓越獎品 Premier Trophy';
}

function cc_company_name() {
    return cc_is_en() ? 'Premier Trophy' : '卓越獎品 Premier Trophy';
}

function cc_shop_page_url( $lang = '' ) {
    $lang = $lang ? sanitize_key( $lang ) : cc_current_lang();
    $url  = cc_base_home_url( '/shop/' );

    if ( $lang === 'en' ) {
        $url = add_query_arg( 'lang', 'en', $url );
    }

    return $url;
}

function cc_theme_asset_url( $relative_path ) {
    return trailingslashit( get_stylesheet_directory_uri() ) . ltrim( (string) $relative_path, '/' );
}

function cc_fallback_media_map() {
    return [
        'products' => [
            39454 => 'assets/category-medal.svg',
            39456 => 'assets/category-medal.svg',
            39466 => 'assets/category-commemorative-plate.svg',
            39468 => 'assets/category-commemorative-plate.svg',
        ],
        'terms'    => [
            332 => 'assets/category-medal.svg',
            346 => 'assets/category-medal.svg',
            335 => 'assets/category-commemorative-plate.svg',
            350 => 'assets/category-commemorative-plate.svg',
        ],
    ];
}

function cc_fallback_media_url_for_product( $product_id ) {
    $map = cc_fallback_media_map();
    $key = (int) $product_id;

    if ( empty( $map['products'][ $key ] ) ) {
        return '';
    }

    return cc_theme_asset_url( $map['products'][ $key ] );
}

function cc_fallback_media_url_for_term( $term_id ) {
    $map = cc_fallback_media_map();
    $key = (int) $term_id;

    if ( empty( $map['terms'][ $key ] ) ) {
        return '';
    }

    return cc_theme_asset_url( $map['terms'][ $key ] );
}

function cc_build_fallback_image_markup( $src, $alt, $class = '' ) {
    $classes = trim( 'cc-fallback-media ' . $class );

    return sprintf(
        '<img src="%1$s" alt="%2$s" class="%3$s" loading="lazy" decoding="async" width="1200" height="1200" />',
        esc_url( $src ),
        esc_attr( $alt ),
        esc_attr( $classes )
    );
}

function cc_product_fallback_alt( $product_id ) {
    return get_the_title( (int) $product_id ) ?: ( cc_is_en() ? 'Premier Trophy product image' : 'Premier Trophy 產品圖片' );
}

function cc_featured_product_ids( $lang = '' ) {
    $lang = $lang ? sanitize_key( $lang ) : cc_current_lang();

    if ( $lang === 'en' ) {
        return [ 39452, 39456, 39464, 39460, 39468, 39545, 39472, 39476 ];
    }

    return [ 39450, 39454, 39462, 39458, 39466, 39543, 39470, 39474 ];
}

function cc_decode_u_sequences( $content ) {
    if ( ! is_string( $content ) || ! preg_match( '/u[0-9a-fA-F]{4}/', $content ) ) {
        return $content;
    }

    return preg_replace_callback(
        '/(?:u[0-9a-fA-F]{4})+/',
        static function ( $matches ) {
            $json = '"' . preg_replace( '/u([0-9a-fA-F]{4})/', '\\\\u$1', $matches[0] ) . '"';
            $decoded = json_decode( $json );

            return is_string( $decoded ) ? $decoded : $matches[0];
        },
        $content
    );
}

function cc_meta_copy() {
    if ( is_front_page() ) {
        return cc_is_en()
            ? [
                'title' => 'Premier Trophy | Custom Trophies, Medals & Corporate Gifts in Hong Kong',
                'desc'  => 'Custom trophies, medals, crystal awards and corporate gifts for schools, institutions and events in Hong Kong.',
            ]
            : [
                'title' => '卓越獎品 | 香港精製獎盃・獎牌・企業禮品訂製',
                'desc'  => '香港本地廠商，專業訂製獎盃、獎牌、水晶獎座、銀碟及企業禮品。一站式設計及生產服務，品質保證，歡迎批量訂購。',
            ];
    }

    if ( is_shop() ) {
        return cc_is_en()
            ? [
                'title' => 'Shop Trophies, Medals & Awards | Premier Trophy Hong Kong',
                'desc'  => 'Browse custom trophies, medals, crystal awards and corporate gifts for Hong Kong schools, institutions and events.',
            ]
            : [
                'title' => '產品分類 | 卓越獎品 Premier Trophy',
                'desc'  => '瀏覽獎盃、獎牌、水晶獎座、木盾、銀碟及企業禮品，適合學校、機構、協會及大型活動訂製查詢。',
            ];
    }

    if ( is_page( [ 38309, 41116 ] ) ) {
        return cc_is_en()
            ? [
                'title' => 'Contact Premier Trophy | Trophy, Medal & Gift Enquiries in Hong Kong',
                'desc'  => 'Contact Premier Trophy for custom trophies, medals, certificates and corporate gift enquiries in Hong Kong. We reply with practical quotation guidance fast.',
            ]
            : [
                'title' => '聯絡我們 | 卓越獎品 Premier Trophy 訂製查詢',
                'desc'  => '聯絡卓越獎品 Premier Trophy，查詢獎盃、獎牌、證書、水晶獎座及企業禮品訂製服務。我們會盡快回覆報價及交期安排。',
            ];
    }

    if ( is_page( [ 38679, 41101 ] ) ) {
        return cc_is_en()
            ? [
                'title' => 'About Premier Trophy | Hong Kong Trophy & Corporate Gift Specialist',
                'desc'  => 'Learn about Premier Trophy, a Hong Kong supplier for custom trophies, medals, certificates and corporate gifts for schools, institutions and events.',
            ]
            : [
                'title' => '關於我們 | 卓越獎品 Premier Trophy',
                'desc'  => '了解卓越獎品 Premier Trophy 的服務背景。我們為香港學校、機構及活動提供獎盃、獎牌、證書及企業禮品訂製。',
            ];
    }

    if ( is_page( [ 38221, 41134 ] ) ) {
        return cc_is_en()
            ? [
                'title' => 'Case Studies | Premier Trophy Projects for Schools & Institutions',
                'desc'  => 'View Premier Trophy case studies across schools, institutions, ceremonies and corporate events in Hong Kong.',
            ]
            : [
                'title' => '客戶案例 | 卓越獎品 Premier Trophy',
                'desc'  => '瀏覽卓越獎品 Premier Trophy 的學校、機構、頒獎典禮及企業活動案例，了解實際訂製成果。',
            ];
    }

    if ( is_page( [ 38310, 41110 ] ) ) {
        return cc_is_en()
            ? [
                'title' => 'Art & Craft Gifts | Custom Presentation Pieces | Premier Trophy',
                'desc'  => 'Explore art and craft presentation gifts, customised display pieces and event-ready commemorative items from Premier Trophy.',
            ]
            : [
                'title' => '藝術工藝 | 卓越獎品 Premier Trophy',
                'desc'  => '瀏覽卓越獎品 Premier Trophy 的藝術工藝及展示禮品，適合典禮、紀念及機構贈送用途。',
            ];
    }

    if ( is_cart() || is_page( [ 40204, 41086 ] ) ) {
        return cc_is_en()
            ? [
                'title' => 'Quote List | Premier Trophy Hong Kong',
                'desc'  => 'Review your selected trophies, medals and corporate gifts, then send a quote enquiry to Premier Trophy.',
            ]
            : [
                'title' => '詢價清單 | 卓越獎品 Premier Trophy',
                'desc'  => '檢視已加入的獎盃、獎牌及企業禮品，並向卓越獎品 Premier Trophy 提交訂製查詢。',
            ];
    }

    if ( is_product_category() ) {
        $copy = cc_product_category_copy();
        if ( $copy ) {
            return [
                'title' => $copy['title'],
                'desc'  => $copy['desc'],
            ];
        }
    }

    return null;
}

function cc_product_category_copy() {
    if ( ! is_product_category() ) {
        return null;
    }

    $term = get_queried_object();
    if ( ! $term instanceof WP_Term ) {
        return null;
    }

    $lang = cc_current_lang();

    $en = [
        298 => [ 'title' => '3D Crystal Awards | Premier Trophy Hong Kong', 'desc' => 'Premium 3D crystal awards with internal laser engraving for executive recognition, commemorative gifting and formal events in Hong Kong.', 'intro' => '3D crystal awards suit executive recognition, commemorative gifting and premium presentations where the piece needs to feel substantial and ceremonial.' ],
        341 => [ 'title' => 'Acrylic Awards | Premier Trophy Hong Kong', 'desc' => 'Modern acrylic awards for schools, institutions and business recognition in Hong Kong.', 'intro' => 'Acrylic awards offer a cleaner, more contemporary presentation format for schools, institutions and organisations that want strong visual clarity without moving into crystal pricing.' ],
        342 => [ 'title' => 'Promotional Gifts | Premier Trophy Hong Kong', 'desc' => 'Custom promotional gifts for campaigns, events, conferences and school programmes in Hong Kong.', 'intro' => 'Promotional gifts work best when the goal is practical brand visibility, event support or a lighter commemorative item that can be distributed at scale.' ],
        343 => [ 'title' => 'Custom Flags & Banners | Premier Trophy Hong Kong', 'desc' => 'Custom flags and banners for schools, institutions, teams and ceremonial presentation in Hong Kong.', 'intro' => 'Flags and banners reinforce identity and stage presence at school ceremonies, institutional functions, team events and processional settings.' ],
        344 => [ 'title' => 'Custom Plaques | Premier Trophy Hong Kong', 'desc' => 'Custom plaques for institutional honours, retirement tributes and formal recognition in Hong Kong.', 'intro' => 'Plaques are a strong choice when the presentation needs to feel formal, display-ready and appropriate for executive, institutional or commemorative use.' ],
        345 => [ 'title' => 'Crystal Trophies | Premier Trophy Hong Kong', 'desc' => 'Premium crystal trophies for executive recognition, donor honours and formal ceremonies in Hong Kong.', 'intro' => 'Crystal trophies are used when clarity, weight and finish matter as much as the wording on the piece itself, especially for boardrooms, stage presentations and donor recognition.' ],
        346 => [ 'title' => 'Custom Medals | Premier Trophy Hong Kong', 'desc' => 'Custom medals for competitions, sports days, races and recognition events in Hong Kong.', 'intro' => 'Medals remain one of the most practical award formats for schools, races, leagues and participation events because they scale cleanly and present well on the day.' ],
        347 => [ 'title' => 'Custom Trophies | Premier Trophy Hong Kong', 'desc' => 'Custom trophies for schools, institutions, sports competitions and award ceremonies in Hong Kong.', 'intro' => 'Custom trophies are the most common choice for school prizegiving, sports competitions, annual recognition and institutional ceremonies where the award needs a clear on-stage presence.' ],
        348 => [ 'title' => 'Pin Badges | Premier Trophy Hong Kong', 'desc' => 'Custom pin badges for schools, clubs, associations and branded events in Hong Kong.', 'intro' => 'Pin badges are useful for recognition programmes, memberships, commemorative distributions and branded campaigns that need a compact, wearable format.' ],
        349 => [ 'title' => 'Custom Certificates | Premier Trophy Hong Kong', 'desc' => 'Custom certificates for schools, institutions, competitions and formal recognition programmes in Hong Kong.', 'intro' => 'Certificates are ideal for academic recognition, completion awards, participation programmes and formal appreciation where presentation quality still matters even without a trophy format.' ],
        350 => [ 'title' => 'Commemorative Plates | Premier Trophy Hong Kong', 'desc' => 'Commemorative plates for anniversaries, guest honours and ceremonial gifting in Hong Kong.', 'intro' => 'Commemorative plates suit anniversaries, guest-of-honour presentations and ceremonial gifting where a more traditional, protocol-friendly format is expected.' ],
    ];

    $zh = [
        330 => [ 'title' => '3D 水晶訂製 | 卓越獎品 Premier Trophy 香港', 'desc' => '適合高級嘉賓紀念、機構表揚及典禮贈送的 3D 水晶獎項訂製服務。', 'intro' => '3D 水晶適合用於高級嘉賓紀念、機構表揚及正式典禮贈送，重點在於內雕效果、份量感及展示質感。' ],
        337 => [ 'title' => '亞加力膠獎項訂製 | 卓越獎品 Premier Trophy 香港', 'desc' => '現代感亞加力膠獎項，適合學校、機構及企業表揚場合。', 'intro' => '亞加力膠獎項外觀簡潔、成本較易控制，適合學校、機構及企業活動使用，亦方便配合品牌圖像及文字排版。' ],
        340 => [ 'title' => '廣告禮品訂製 | 卓越獎品 Premier Trophy 香港', 'desc' => '適合活動、品牌推廣、會議及機構派發的廣告禮品訂製服務。', 'intro' => '廣告禮品適合活動推廣、品牌曝光及大批量派發用途，重點在於實用性、識別度及整體呈現是否整齊一致。' ],
        334 => [ 'title' => '旗幟訂製 | 卓越獎品 Premier Trophy 香港', 'desc' => '學校、機構、隊伍及典禮場合適用的旗幟及橫額訂製服務。', 'intro' => '旗幟及橫額常用於學校典禮、隊伍展示、機構活動及進場場合，重點在於識別度、尺寸比例及現場展示效果。' ],
        339 => [ 'title' => '木盾訂製 | 卓越獎品 Premier Trophy 香港', 'desc' => '適合機構嘉許、退休榮譽及正式紀念用途的木盾訂製服務。', 'intro' => '木盾適合較正式的嘉許及紀念場合，例如退休致謝、校務嘉許及機構表揚，展示感較為穩重。' ],
        331 => [ 'title' => '水晶獎座訂製 | 卓越獎品 Premier Trophy 香港', 'desc' => '高級水晶獎座，適合行政表揚、典禮頒發及重要嘉賓紀念。', 'intro' => '水晶獎座適合需要高級感與份量感的場合，例如董事會嘉許、行政表揚、贊助鳴謝及重要典禮頒發。' ],
        332 => [ 'title' => '獎牌訂製 | 卓越獎品 Premier Trophy 香港', 'desc' => '適合比賽、運動會、跑步活動及頒獎典禮的獎牌訂製服務。', 'intro' => '獎牌適合學校運動會、比賽、跑步活動及參與獎，製作效率高，現場佩戴效果亦較明顯。' ],
        333 => [ 'title' => '獎盃訂製 | 卓越獎品 Premier Trophy 香港', 'desc' => '適合學校、機構、比賽及企業頒獎典禮的獎盃訂製服務。', 'intro' => '獎盃是最常見的頒獎形式，適合學校、體育賽事、社區活動及企業嘉許，重點在於現場呈現及整體儀式感。' ],
        336 => [ 'title' => '襟章訂製 | 卓越獎品 Premier Trophy 香港', 'desc' => '適合學校團隊、機構會員及品牌活動的襟章訂製服務。', 'intro' => '襟章適合學校隊伍、會員計劃、紀念活動及品牌推廣，體積小、佩戴方便，亦適合大量派發。' ],
        338 => [ 'title' => '證書訂製 | 卓越獎品 Premier Trophy 香港', 'desc' => '適合學校、機構、課程及典禮頒授的證書訂製服務。', 'intro' => '證書常用於學術表揚、課程完成、參與證明及機構嘉許，重點在於版面、文字層次及正式感。' ],
        335 => [ 'title' => '銀碟訂製 | 卓越獎品 Premier Trophy 香港', 'desc' => '適合周年紀念、嘉賓致送及正式儀式場合的銀碟訂製服務。', 'intro' => '銀碟較適合周年紀念、重要嘉賓致送及正式儀式場合，屬較傳統而穩重的展示形式。' ],
    ];

    $map = ( $lang === 'en' ) ? $en : $zh;
    if ( isset( $map[ $term->term_id ] ) ) {
        return $map[ $term->term_id ];
    }

    $label = single_term_title( '', false );

    return [
        'title' => cc_is_en()
            ? sprintf( '%s | Premier Trophy Hong Kong', $label )
            : sprintf( '%s | 卓越獎品 Premier Trophy 香港', $label ),
        'desc'  => cc_is_en()
            ? sprintf( 'Browse %s for schools, institutions, ceremonies and corporate events in Hong Kong.', strtolower( $label ) )
            : sprintf( '瀏覽 %s 產品，適合香港學校、機構、典禮及企業活動訂製。', $label ),
        'intro' => '',
    ];
}

add_filter( 'option_blogname', function ( $value ) {
    return is_admin() ? $value : cc_brand_name();
} );

add_filter( 'option_blogdescription', function ( $value ) {
    if ( is_admin() ) {
        return $value;
    }

    return cc_is_en()
        ? 'Premier Trophy supplies custom trophies, medals and corporate gifts in Hong Kong.'
        : 'Premier Trophy 提供香港精製獎盃、獎牌及企業禮品訂製。';
} );

add_filter( 'wpseo_opengraph_site_name', function () {
    return cc_brand_name();
} );

add_filter( 'wpseo_schema_organization', function ( $data ) {
    if ( ! is_array( $data ) ) {
        return $data;
    }

    $logo_id   = 38605;
    $logo_url  = wp_get_attachment_url( $logo_id );
    $logo_meta = wp_get_attachment_metadata( $logo_id );

    $data['name']                   = cc_company_name();
    $data['legalName']              = 'Premier Trophy';
    $data['alternateName']          = '卓越獎品';
    $data['email']                  = 'info@ptrophy.com';
    $data['telephone']              = '+852 2151 3944';
    $data['description']            = cc_is_en()
        ? 'Premier Trophy supplies custom trophies, medals, plaques, certificates and corporate gifts for Hong Kong schools, institutions and events.'
        : 'Premier Trophy 為香港學校、機構及大型活動提供獎盃、獎牌、證書、水晶獎座及企業禮品訂製服務。';
    $data['sameAs']                 = array_values( array_filter( $data['sameAs'] ?? [] ) );
    if ( empty( $data['contactPoint'] ) || ! is_array( $data['contactPoint'] ) ) {
        $data['contactPoint'] = [ [] ];
    }
    $data['contactPoint'][0]['url'] = cc_contact_page_url( cc_current_lang() );
    $data['contactPoint'][0]['availableLanguage'] = [ 'en', 'zh-hant' ];

    if ( $logo_url ) {
        $logo_object = [
            '@type'      => 'ImageObject',
            '@id'        => home_url( '/#/schema/logo/image/' ),
            'url'        => $logo_url,
            'contentUrl' => $logo_url,
            'caption'    => cc_company_name(),
        ];

        if ( ! empty( $logo_meta['width'] ) ) {
            $logo_object['width'] = (int) $logo_meta['width'];
        }

        if ( ! empty( $logo_meta['height'] ) ) {
            $logo_object['height'] = (int) $logo_meta['height'];
        }

        $data['logo']  = $logo_object;
        $data['image'] = $logo_object;
    }

    return $data;
}, 99 );

add_filter( 'wpseo_schema_website', function ( $data ) {
    if ( ! is_array( $data ) ) {
        return $data;
    }

    $data['name']          = cc_brand_name();
    $data['alternateName'] = 'Premier Trophy';

    // Fix EN SearchAction URL: Yoast generates ?lang=en?s= (double ?) instead of ?lang=en&s=
    if ( ! empty( $data['potentialAction'] ) && is_array( $data['potentialAction'] ) ) {
        foreach ( $data['potentialAction'] as &$action ) {
            if ( isset( $action['target']['urlTemplate'] ) ) {
                $action['target']['urlTemplate'] = preg_replace(
                    '/\?lang=([a-z]+)\?s=/',
                    '?lang=$1&s=',
                    $action['target']['urlTemplate']
                );
            }
        }
    }

    return $data;
} );

// ── Product schema (Yoast free doesn't emit Product type without WC SEO plugin) ─
add_action( 'wp_head', function () {
    if ( ! is_product() ) {
        return;
    }
    global $product;
    if ( ! $product instanceof WC_Product ) {
        $product = wc_get_product( get_the_ID() );
    }
    if ( ! $product ) {
        return;
    }

    $name        = get_the_title();
    $desc        = wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() );
    $url         = get_permalink();
    $image_id    = $product->get_image_id();
    $image_url   = $image_id ? wp_get_attachment_url( $image_id ) : '';
    $org_id      = home_url( '/#organization' );
    $brand_name  = cc_company_name();

    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'Product',
        'name'     => $name,
        'url'      => $url,
        'brand'    => [ '@type' => 'Brand', 'name' => $brand_name ],
        'seller'   => [ '@id' => $org_id ],
        'offers'   => [
            '@type'           => 'Offer',
            'url'             => $url,
            'priceCurrency'   => 'HKD',
            'price'           => '0',
            'availability'    => 'https://schema.org/InStock',
            'itemCondition'   => 'https://schema.org/NewCondition',
            'seller'          => [ '@id' => $org_id ],
        ],
    ];

    if ( $desc ) {
        $schema['description'] = $desc;
    }

    if ( $image_url ) {
        $schema['image'] = $image_url;
    }

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 10 );

add_filter( 'wpseo_schema_webpage', function ( $data ) {
    if ( ! is_array( $data ) ) {
        return $data;
    }

    $copy = cc_meta_copy();
    if ( ! $copy ) {
        return $data;
    }

    $data['name']        = $copy['title'];
    $data['description'] = $copy['desc'];

    return $data;
} );

add_filter( 'wpseo_title', function ( $title ) {
    $copy = cc_meta_copy();
    return $copy ? $copy['title'] : $title;
} );

add_filter( 'wpseo_metadesc', function ( $description ) {
    $copy = cc_meta_copy();
    return $copy ? $copy['desc'] : $description;
} );

add_filter( 'wpseo_opengraph_title', function ( $title ) {
    $copy = cc_meta_copy();
    return $copy ? $copy['title'] : $title;
} );

add_filter( 'wpseo_opengraph_desc', function ( $description ) {
    $copy = cc_meta_copy();
    return $copy ? $copy['desc'] : $description;
} );

add_filter( 'wpseo_twitter_title', function ( $title ) {
    $copy = cc_meta_copy();
    return $copy ? $copy['title'] : $title;
} );

add_filter( 'wpseo_twitter_description', function ( $description ) {
    $copy = cc_meta_copy();
    return $copy ? $copy['desc'] : $description;
} );

add_filter( 'pre_get_document_title', function ( $title ) {
    if ( is_admin() ) {
        return $title;
    }

    $copy = cc_meta_copy();

    return $copy ? $copy['title'] : $title;
}, 20 );

add_filter( 'wpseo_opengraph_image', function ( $url ) {
    if ( ! is_singular( 'product' ) ) {
        return $url;
    }

    $fallback = cc_fallback_media_url_for_product( get_queried_object_id() );

    return $fallback ?: $url;
} );

add_filter( 'wpseo_twitter_image', function ( $url ) {
    if ( ! is_singular( 'product' ) ) {
        return $url;
    }

    $fallback = cc_fallback_media_url_for_product( get_queried_object_id() );

    return $fallback ?: $url;
} );

// WPML registers product_cat rewrite rules with the "category/" slug (a stale ZH
// translation), so product-category/{slug}/ URLs fall through to the attachment
// catch-all rewrite rule. Intercept these before the query runs and fix the vars.
add_filter( 'request', function ( $query_vars ) {
    // The URL may be parsed as attachment=trophy (from the attachment catch-all rule)
    // or as pagename=product-category/trophy — check the REQUEST_URI directly.
    $path = isset( $_SERVER['REQUEST_URI'] ) ? strtok( $_SERVER['REQUEST_URI'], '?' ) : '';
    if ( preg_match( '#^/product-category/([^/]+)(?:/page/(\d+))?/?$#', $path, $m ) ) {
        $new_vars = [ 'product_cat' => $m[1] ];
        if ( ! empty( $m[2] ) ) {
            $new_vars['paged'] = (int) $m[2];
        }
        return $new_vars;
    }
    return $query_vars;
}, 1 );

// Prevent WordPress redirect_canonical from incorrectly redirecting WooCommerce
// product category archive pages (ZH categories with English slugs) to product pages.
add_filter( 'redirect_canonical', function ( $redirect_url, $requested_url ) {
    if ( is_product_category() ) {
        return false;
    }
    return $redirect_url;
}, 1, 2 );

add_filter( 'woocommerce_page_title', function ( $title ) {
    if ( is_shop() ) {
        return cc_is_en() ? 'Custom Trophies, Medals &amp; Awards Hong Kong' : '產品分類';
    }

    if ( is_cart() ) {
        return cc_is_en() ? 'Quote List' : '詢價清單';
    }

    if ( is_product_category() && cc_is_en() ) {
        $term   = get_queried_object();
        $h1_map = [
            347 => 'Custom Trophies Hong Kong',
            346 => 'Custom Medals Hong Kong',
            345 => 'Crystal Trophies Hong Kong',
            344 => 'Custom Plaques Hong Kong',
            350 => 'Commemorative Plates Hong Kong',
            349 => 'Custom Certificates Hong Kong',
            343 => 'Custom Flags &amp; Banners Hong Kong',
            342 => 'Promotional Gifts Hong Kong',
            348 => 'Custom Pin Badges Hong Kong',
            298 => '3D Crystal Awards Hong Kong',
            341 => 'Acrylic Awards Hong Kong',
        ];
        if ( $term instanceof WP_Term && isset( $h1_map[ $term->term_id ] ) ) {
            return $h1_map[ $term->term_id ];
        }
    }

    return $title;
} );

add_filter( 'get_the_excerpt', function ( $excerpt, $post ) {
    if ( is_admin() || ! is_shop() || ! $post instanceof WP_Post ) {
        return $excerpt;
    }

    if ( (int) $post->ID !== (int) wc_get_page_id( 'shop' ) ) {
        return $excerpt;
    }

    $copy = cc_meta_copy();
    return $copy ? $copy['desc'] : $excerpt;
}, 10, 2 );

add_filter( 'the_title', function ( $title, $post_id ) {
    if ( is_admin() || ! $post_id ) {
        return $title;
    }

    if ( is_shop() && (int) $post_id === (int) wc_get_page_id( 'shop' ) ) {
        return cc_is_en() ? 'Custom Trophies, Medals &amp; Awards Hong Kong' : '產品分類';
    }

    if ( is_cart() && (int) $post_id === (int) wc_get_page_id( 'cart' ) ) {
        return cc_is_en() ? 'Quote List' : '詢價清單';
    }

    return $title;
}, 10, 2 );

// ── Cart / Checkout page H1 (Elementor hides the WP page title) ─────────────
add_filter( 'the_content', function ( $content ) {
    if ( is_admin() ) {
        return $content;
    }
    $page_id = get_the_ID();
    $cart_ids     = [ 40204, 41086 ];
    $checkout_ids = [ 40193, 41095 ]; // ZH checkout, EN checkout

    if ( in_array( (int) $page_id, $cart_ids, true ) ) {
        $h1 = cc_is_en() ? 'Quote List' : '詢價清單';
    } elseif ( in_array( (int) $page_id, $checkout_ids, true ) ) {
        $h1 = cc_is_en() ? 'Submit Your Quote Request' : '提交詢價';
    } else {
        return $content;
    }

    return '<h1 class="cc-page-h1">' . esc_html( $h1 ) . '</h1>' . $content;
}, 999 );

add_filter( 'woocommerce_get_breadcrumb', function ( $crumbs ) {
    if ( is_admin() || empty( $crumbs ) ) {
        return $crumbs;
    }

    foreach ( $crumbs as &$crumb ) {
        if ( ! isset( $crumb[0] ) ) {
            continue;
        }

        if ( ! cc_is_en() && $crumb[0] === 'Shop' ) {
            $crumb[0] = '產品分類';
        }

        if ( cc_is_en() && $crumb[0] === '首頁' ) {
            $crumb[0] = 'Home';
        }

        if ( isset( $crumb[1] ) ) {
            if ( in_array( $crumb[0], [ 'Home', '首頁' ], true ) ) {
                $crumb[1] = cc_resolve_page_url( [ 'zh-hant' => 38090, 'en' => 41143, 'default' => 38090 ], [ 'home' ], cc_current_lang() );
            }

            if ( in_array( $crumb[0], [ 'Shop', '產品分類' ], true ) ) {
                $crumb[0] = cc_is_en() ? 'Product Categories' : '產品分類';
                $crumb[1] = cc_shop_page_url( cc_current_lang() );
            }
        }
    }

    return $crumbs;
}, 10 );

add_filter( 'wpseo_breadcrumb_links', function ( $links ) {
    if ( is_admin() || empty( $links ) ) {
        return $links;
    }

    foreach ( $links as &$link ) {
        if ( ! isset( $link['text'] ) ) {
            continue;
        }

        if ( ! cc_is_en() && $link['text'] === 'Shop' ) {
            $link['text'] = '產品分類';
        }

        if ( cc_is_en() && $link['text'] === '首頁' ) {
            $link['text'] = 'Home';
        }

        if ( isset( $link['url'] ) ) {
            if ( in_array( $link['text'], [ '首頁', 'Home' ], true ) ) {
                $link['url'] = cc_resolve_page_url( [ 'zh-hant' => 38090, 'en' => 41143, 'default' => 38090 ], [ 'home' ], cc_current_lang() );
            }

            if ( in_array( $link['text'], [ 'Shop', '產品分類', 'Product Categories' ], true ) ) {
                $link['text'] = cc_is_en() ? 'Product Categories' : '產品分類';
                $link['url']  = cc_shop_page_url( cc_current_lang() );
            }
        }
    }

    return $links;
}, 20 );

// ── Price-Hidden Quote Basket Mode ───────────────────────────────────────────

// 1. Hide all prices site-wide
add_filter( 'woocommerce_get_price_html', '__return_empty_string' );

// 2. Remove price from single product page, but keep the basket flow active.
add_action( 'wp', function () {
    if ( ! is_product() ) return;
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
} );

// 3. Remove loop price only. Keep add-to-cart available for the quote basket.
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );

// 4. Pre-fill the enquiry form product field from URL parameter (Elementor Pro)
add_filter( 'elementor_pro/forms/field_value', function ( $value, $field ) {
    if ( isset( $field['field_id'] ) && $field['field_id'] === 'product_name' ) {
        $from_url = sanitize_text_field( wp_unslash( $_GET['product'] ?? '' ) );
        if ( $from_url ) return $from_url;
    }
    return $value;
}, 10, 2 );

add_filter( 'woocommerce_product_add_to_cart_text', function ( $text, $product ) {
    if ( is_admin() ) {
        return $text;
    }

    return cc_is_en() ? 'Add to Quote List' : '加入查詢清單';
}, 20, 2 );

add_filter( 'woocommerce_product_single_add_to_cart_text', function ( $text, $product ) {
    if ( is_admin() ) {
        return $text;
    }

    return cc_is_en() ? 'Add to Quote List' : '加入查詢清單';
}, 20, 2 );

// 5. Keep products purchasable so the basket and custom field flow still works
// even though prices are hidden and blank in the catalog.
add_filter( 'woocommerce_is_purchasable', function ( $purchasable, $product ) {
    if ( is_admin() || ! $product instanceof WC_Product ) {
        return $purchasable;
    }

    return true;
}, 20, 2 );

add_filter( 'woocommerce_variation_is_purchasable', function ( $purchasable, $variation ) {
    if ( is_admin() || ! $variation instanceof WC_Product_Variation ) {
        return $purchasable;
    }

    return true;
}, 20, 2 );

add_action( 'template_redirect', function () {
    if ( is_admin() ) {
        return;
    }

    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $path        = trim( (string) wp_parse_url( home_url( $request_uri ), PHP_URL_PATH ), '/' );

    if ( $path === 'products' ) {
        wp_safe_redirect( cc_shop_page_url(), 301 );
        exit;
    }

    if ( ! str_starts_with( $path, 'cart/' ) ) {
        return;
    }

    $segments = array_values( array_filter( explode( '/', $path ) ) );
    if ( count( $segments ) < 3 ) {
        return;
    }

    $legacy_slug = urldecode( end( $segments ) );
    $product     = get_posts( [
        'name'              => $legacy_slug,
        'post_type'         => 'product',
        'post_status'       => 'publish',
        'numberposts'       => 1,
        'suppress_filters'  => false,
    ] );

    if ( empty( $product ) ) {
        return;
    }

    $target = get_permalink( $product[0] );
    if ( ! $target ) {
        return;
    }

    wp_safe_redirect( $target, 301 );
    exit;
}, 1 );

add_filter( 'the_content', function ( $content ) {
    if ( is_admin() || ! is_singular( 'product' ) ) {
        return $content;
    }

    return cc_decode_u_sequences( $content );
}, 20 );

add_action( 'wp', function () {
    if ( is_admin() ) {
        return;
    }

    if ( is_shop() || is_product_taxonomy() ) {
        remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
        remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
    }

    remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
    // Belt-and-suspenders: suppress WooCommerce term_description output when a custom intro exists.
    add_filter( 'term_description', function ( $description ) {
        if ( is_product_category() ) {
            $copy = cc_product_category_copy();
            if ( $copy && ! empty( $copy['intro'] ) ) {
                return '';
            }
        }
        return $description;
    }, 5 );
    add_action( 'woocommerce_archive_description', function () {
        if ( is_product_category() ) {
            $copy = cc_product_category_copy();
            if ( $copy && ! empty( $copy['intro'] ) ) {
                echo '<div class="term-description"><p>' . esc_html( $copy['intro'] ) . '</p></div>';
                return;
            }

            woocommerce_product_archive_description();
            return;
        }

        if ( is_shop() ) {
            $copy = cc_meta_copy();
            if ( ! $copy ) {
                return;
            }

            echo '<div class="page-description"><p>' . esc_html( $copy['desc'] ) . '</p></div>';
            return;
        }

        woocommerce_product_archive_description();
    }, 10 );
}, 20 );

add_filter( 'woocommerce_subcategory_count_html', function () {
    return '';
}, 20 );

add_filter( 'elementor/widget/render_content', function ( $content ) {
    if ( is_admin() || ! is_string( $content ) || $content === '' ) {
        return $content;
    }

    $cta_label   = cc_is_en() ? 'Contact Us' : '聯絡我們';
    $cta_url     = get_permalink( cc_is_en() ? 41116 : 38309 );
    $shop_url    = cc_shop_page_url();
    $placeholder = cc_is_en() ? 'Search trophies, medals or gifts' : '搜尋獎盃、獎牌或禮品';

    $content = str_replace( 'Order Now!', $cta_label, $content );
    $content = str_replace( 'Type to start searching...', $placeholder, $content );
    $content = preg_replace(
        '#href=(["\'])https?://www\.crystalcentury\.com/product/?\1#',
        'href=' . wp_json_encode( esc_url( $cta_url ) ),
        $content
    );
    $content = preg_replace(
        '#href=(["\'])https?://www\.crystalcentury\.com/products/?\1#',
        'href=' . wp_json_encode( esc_url( $shop_url ) ),
        $content
    );

    return $content;
}, 20 );

add_filter( 'gettext', function ( $translated, $text ) {
    if ( is_admin() ) {
        return $translated;
    }

    $replacements = cc_is_en()
        ? [
            'Cart'                => 'Quote List',
            'Add to cart'         => 'Add to Quote List',
            'View cart'           => 'View Quote List',
            'Proceed to checkout' => 'Submit Quote Request',
            'Your cart is currently empty.' => 'Your quote list is currently empty.',
            'Return to shop'      => 'Browse products',
        ]
        : [
            'Cart'                => '詢價清單',
            'Add to cart'         => '加入查詢清單',
            'View cart'           => '查看詢價清單',
            'Proceed to checkout' => '提交查詢',
            'Your cart is currently empty.' => '目前詢價清單尚未加入任何產品。',
            'Return to shop'      => '瀏覽產品',
        ];

    if ( isset( $replacements[ $text ] ) ) {
        return $replacements[ $text ];
    }

    if ( $text === 'Type to start searching...' ) {
        return cc_is_en() ? 'Search trophies, medals or gifts' : '搜尋獎盃、獎牌或禮品';
    }

    if ( ! cc_is_en() && $text === 'Shop' ) {
        return '產品分類';
    }

    return $translated;
}, 20, 2 );

// Translate custom checkout field labels for EN visitors.
add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
    if ( ! cc_is_en() ) {
        return $fields;
    }
    $label_map = [
        'billing_estimateddate' => 'Estimated Delivery Date',
        'billing_deliverymethod' => 'Collection Method',
        'billing_inquiry'       => 'Enquiry Details',
    ];
    foreach ( $label_map as $key => $label ) {
        if ( isset( $fields['billing'][ $key ] ) ) {
            $fields['billing'][ $key ]['label'] = $label;
        }
    }
    // Translate delivery method options if present
    if ( isset( $fields['billing']['billing_deliverymethod']['options'] ) ) {
        $opt_map = [ '自取' => 'Self-pickup', '送貨' => 'Delivery' ];
        foreach ( $fields['billing']['billing_deliverymethod']['options'] as $k => $v ) {
            if ( isset( $opt_map[ $v ] ) ) {
                $fields['billing']['billing_deliverymethod']['options'][ $k ] = $opt_map[ $v ];
            }
        }
    }
    return $fields;
}, 20 );

add_filter( 'post_thumbnail_html', function ( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
    if ( is_admin() || get_post_type( $post_id ) !== 'product' ) {
        return $html;
    }

    $fallback_src = cc_fallback_media_url_for_product( $post_id );
    if ( ! $fallback_src ) {
        return $html;
    }

    $classes = 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail wp-post-image';
    if ( is_array( $attr ) && ! empty( $attr['class'] ) ) {
        $classes .= ' ' . trim( (string) $attr['class'] );
    }

    return cc_build_fallback_image_markup( $fallback_src, get_the_title( $post_id ), $classes );
}, 20, 5 );

add_filter( 'woocommerce_product_get_image', function ( $image, $product, $size, $attr ) {
    if ( is_admin() || ! $product instanceof WC_Product ) {
        return $image;
    }

    $fallback_src = cc_fallback_media_url_for_product( $product->get_id() );
    if ( ! $fallback_src ) {
        return $image;
    }

    $classes = 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail';
    if ( is_array( $attr ) && ! empty( $attr['class'] ) ) {
        $classes .= ' ' . trim( (string) $attr['class'] );
    }

    return cc_build_fallback_image_markup( $fallback_src, cc_product_fallback_alt( $product->get_id() ), $classes );
}, 20, 4 );

add_filter( 'woocommerce_single_product_image_thumbnail_html', function ( $html, $attachment_id ) {
    if ( is_admin() || ! is_product() ) {
        return $html;
    }

    $product_id    = get_queried_object_id();
    $fallback_src  = cc_fallback_media_url_for_product( $product_id );

    if ( ! $fallback_src ) {
        return $html;
    }

    $alt = cc_product_fallback_alt( $product_id );

    return sprintf(
        '<div data-thumb="%1$s" data-thumb-alt="%2$s" class="woocommerce-product-gallery__image"><a href="%1$s"><img width="1200" height="1200" src="%1$s" class="wp-post-image cc-fallback-media" alt="%2$s" data-caption="" data-src="%1$s" data-large_image="%1$s" data-large_image_width="1200" data-large_image_height="1200" decoding="async" /></a></div>',
        esc_url( $fallback_src ),
        esc_attr( $alt )
    );
}, 20, 2 );

add_filter( 'woocommerce_product_get_gallery_image_ids', function ( $image_ids, $product ) {
    if ( is_admin() || ! $product instanceof WC_Product ) {
        return $image_ids;
    }

    if ( cc_fallback_media_url_for_product( $product->get_id() ) ) {
        return [];
    }

    $featured_id  = (int) $product->get_image_id();
    $featured_url = $featured_id ? wp_get_attachment_image_url( $featured_id, 'full' ) : '';
    $deduped      = [];
    $seen_urls    = [];

    foreach ( array_map( 'intval', (array) $image_ids ) as $image_id ) {
        if ( ! $image_id ) {
            continue;
        }

        $url = wp_get_attachment_image_url( $image_id, 'full' );
        if ( ! $url ) {
            continue;
        }

        if ( $featured_url && $url === $featured_url ) {
            continue;
        }

        if ( isset( $seen_urls[ $url ] ) ) {
            continue;
        }

        $seen_urls[ $url ] = true;
        $deduped[]         = $image_id;
    }

    return $deduped;
}, 20, 2 );

add_action( 'wp_footer', function () {
    if ( is_admin() || wp_doing_ajax() || is_feed() ) {
        return;
    }

    echo do_shortcode( '[cc_footer]' );
}, 80 );

add_action( 'wp_footer', function () {
    if ( is_admin() ) {
        return;
    }

    $placeholder = cc_is_en() ? 'Search trophies, medals or gifts' : '搜尋獎盃、獎牌或禮品';
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('input.e-search-input').forEach(function (input) {
        if (!input.getAttribute('placeholder') || input.getAttribute('placeholder') === 'Type to start searching...') {
          input.setAttribute('placeholder', <?php echo wp_json_encode( $placeholder ); ?>);
        }
      });

      document.querySelectorAll('.wpml-ls-statics-footer, #lang_sel_footer, .otgs-development-site-front-end').forEach(function (node) {
        node.remove();
      });

      var footers = Array.from(document.querySelectorAll('[data-cc-footer=\"1\"]'));
      if (footers.length > 1) {
        footers.slice(0, -1).forEach(function (node) { node.remove(); });
      }
    });
    </script>
    <?php
}, 99 );

function cc_footer_links( $lang = '' ) {
    $lang = $lang ? sanitize_key( $lang ) : cc_current_lang();
    $is_en = ( $lang === 'en' );

    $quick = $is_en
        ? [
            [ 'label' => 'Home',         'url' => cc_resolve_page_url( [ 'zh-hant' => 38090, 'en' => 41143, 'default' => 38090 ], [ 'home' ], $lang ) ],
            [ 'label' => 'Shop',         'url' => cc_shop_page_url( $lang ) ],
            [ 'label' => 'About Us',     'url' => cc_resolve_page_url( [ 'zh-hant' => 38679, 'en' => 41101, 'default' => 38679 ], [ 'about-us', 'about', '關於我們' ], $lang ) ],
            [ 'label' => 'Case Studies', 'url' => cc_resolve_page_url( [ 'zh-hant' => 38221, 'en' => 41134, 'default' => 38221 ], [ 'case-studies', '客戶案例' ], $lang ) ],
            [ 'label' => 'Art & Craft',  'url' => cc_resolve_page_url( [ 'zh-hant' => 38310, 'en' => 41110, 'default' => 38310 ], [ 'art-craft', '藝術工藝' ], $lang ) ],
            [ 'label' => 'Contact Us',   'url' => cc_contact_page_url( $lang ) ],
        ]
        : [
            [ 'label' => '首頁',     'url' => cc_resolve_page_url( [ 'zh-hant' => 38090, 'en' => 41143, 'default' => 38090 ], [ 'home' ], $lang ) ],
            [ 'label' => '商品',     'url' => cc_shop_page_url( $lang ) ],
            [ 'label' => '關於我們', 'url' => cc_resolve_page_url( [ 'zh-hant' => 38679, 'en' => 41101, 'default' => 38679 ], [ 'about', '關於我們' ], $lang ) ],
            [ 'label' => '客戶案例', 'url' => cc_resolve_page_url( [ 'zh-hant' => 38221, 'en' => 41134, 'default' => 38221 ], [ 'case-studies', '客戶案例' ], $lang ) ],
            [ 'label' => '藝術工藝', 'url' => cc_resolve_page_url( [ 'zh-hant' => 38310, 'en' => 41110, 'default' => 38310 ], [ 'art-craft', '藝術工藝' ], $lang ) ],
            [ 'label' => '聯絡我們', 'url' => cc_contact_page_url( $lang ) ],
        ];

    $legal = $is_en
        ? [
            [ 'label' => 'Terms of Service',     'url' => cc_resolve_page_url( [ 'zh-hant' => 40972, 'en' => 41076, 'default' => 40972 ], [ 'terms-of-service-en', 'terms-of-service' ], $lang ) ],
            [ 'label' => 'Privacy Policy',       'url' => cc_resolve_page_url( [ 'zh-hant' => 40975, 'en' => 41073, 'default' => 40975 ], [ 'privacy-policy' ], $lang ) ],
            [ 'label' => 'Cookie Policy',        'url' => cc_resolve_page_url( [ 'zh-hant' => 40978, 'en' => 41070, 'default' => 40978 ], [ 'cookie-policy' ], $lang ) ],
            [ 'label' => 'Disclaimer',           'url' => cc_resolve_page_url( [ 'zh-hant' => 40981, 'en' => 41067, 'default' => 40981 ], [ 'disclaimer' ], $lang ) ],
            [ 'label' => 'Shipping Policy',      'url' => cc_resolve_page_url( [ 'zh-hant' => 40995, 'en' => 41058, 'default' => 40995 ], [ 'shipping-policy' ], $lang ) ],
            [ 'label' => 'Returns & Refunds',    'url' => cc_resolve_page_url( [ 'zh-hant' => 40998, 'en' => 41055, 'default' => 40998 ], [ 'returns-refunds' ], $lang ) ],
            [ 'label' => 'Payment Methods',      'url' => cc_resolve_page_url( [ 'zh-hant' => 41010, 'en' => 41043, 'default' => 41010 ], [ 'payment-methods-en', 'payment-methods' ], $lang ) ],
        ]
        : [
            [ 'label' => '服務條款',       'url' => cc_resolve_page_url( [ 'zh-hant' => 40972, 'en' => 41076, 'default' => 40972 ], [ 'terms-of-service' ], $lang ) ],
            [ 'label' => '私隱政策',       'url' => cc_resolve_page_url( [ 'zh-hant' => 40975, 'en' => 41073, 'default' => 40975 ], [ 'privacy-policy' ], $lang ) ],
            [ 'label' => 'Cookie 政策',    'url' => cc_resolve_page_url( [ 'zh-hant' => 40978, 'en' => 41070, 'default' => 40978 ], [ 'cookie-policy' ], $lang ) ],
            [ 'label' => '免責聲明',       'url' => cc_resolve_page_url( [ 'zh-hant' => 40981, 'en' => 41067, 'default' => 40981 ], [ 'disclaimer' ], $lang ) ],
            [ 'label' => '運送政策',       'url' => cc_resolve_page_url( [ 'zh-hant' => 40995, 'en' => 41058, 'default' => 40995 ], [ 'shipping-policy' ], $lang ) ],
            [ 'label' => '退款及退貨政策', 'url' => cc_resolve_page_url( [ 'zh-hant' => 40998, 'en' => 41055, 'default' => 40998 ], [ 'returns-refunds' ], $lang ) ],
            [ 'label' => '付款方式',       'url' => cc_resolve_page_url( [ 'zh-hant' => 41010, 'en' => 41043, 'default' => 41010 ], [ 'payment-methods' ], $lang ) ],
        ];

    return [
        'quick' => $quick,
        'legal' => $legal,
    ];
}

add_shortcode( 'cc_footer', function () {
    $lang  = cc_current_lang();
    $logo  = wp_get_attachment_url( 38605 );
    $links = cc_footer_links( $lang );

    ob_start();
    ?>
    <div class="cc-site-footer-wrap" data-cc-footer="1">
    <div class="cc-site-footer">
      <div class="cc-site-footer__grid">
        <div class="cc-site-footer__brand">
          <a class="cc-site-footer__logo" href="<?php echo esc_url( cc_resolve_page_url( [ 'zh-hant' => 38090, 'en' => 41143, 'default' => 38090 ], [ 'home' ], $lang ) ); ?>">
            <img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( cc_brand_name() ); ?>" loading="lazy">
          </a>
          <p class="cc-site-footer__copy"><?php echo esc_html( cc_is_en() ? 'Custom trophies, medals, certificates and corporate gifts for schools, institutions and events in Hong Kong.' : '為香港學校、機構及大型活動提供獎盃、獎牌、證書及企業禮品訂製服務。' ); ?></p>
          <ul class="cc-site-footer__contact">
            <li><a href="tel:+85221513944">+852 2151 3944</a></li>
            <li><a href="mailto:info@ptrophy.com">info@ptrophy.com</a></li>
          </ul>
        </div>
        <div class="cc-site-footer__nav">
          <h3><?php echo esc_html( cc_is_en() ? 'Quick Links' : '快速連結' ); ?></h3>
          <ul>
            <?php foreach ( $links['quick'] as $link ) : ?>
              <li><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="cc-site-footer__nav">
          <h3><?php echo esc_html( cc_is_en() ? 'Policies' : '條款與政策' ); ?></h3>
          <ul>
            <?php foreach ( $links['legal'] as $link ) : ?>
              <li><a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
    </div>
    <?php
    return (string) ob_get_clean();
} );

add_filter( 'wp_nav_menu_objects', function ( $items ) {
    if ( empty( $items ) || ! is_array( $items ) ) {
        return $items;
    }

    $home_en = add_query_arg( 'lang', 'en', cc_base_home_url( '/' ) );
    $home_zh = cc_base_home_url( '/' );
    $map     = [
        'Home'         => $home_en,
        'Shop'         => cc_shop_page_url( 'en' ),
        'About Us'     => cc_resolve_page_url( [ 'zh-hant' => 38679, 'en' => 41101, 'default' => 38679 ], [ 'about-us', 'about', '關於我們' ], 'en' ),
        'Case Studies' => cc_resolve_page_url( [ 'zh-hant' => 38221, 'en' => 41134, 'default' => 38221 ], [ 'case-studies', '客戶案例' ], 'en' ),
        'Art & Craft'  => cc_resolve_page_url( [ 'zh-hant' => 38310, 'en' => 41110, 'default' => 38310 ], [ 'art-craft', '藝術工藝' ], 'en' ),
        'Contact Us'   => cc_contact_page_url( 'en' ),
        '中文'          => $home_zh,
        '首頁'          => $home_zh,
        '商品'          => cc_shop_page_url( 'zh-hant' ),
        '商品分類'      => cc_shop_page_url( 'zh-hant' ),
        '關於我們'      => cc_resolve_page_url( [ 'zh-hant' => 38679, 'en' => 41101, 'default' => 38679 ], [ '關於我們', 'about-us', 'about' ], 'zh-hant' ),
        '客戶案例'      => cc_resolve_page_url( [ 'zh-hant' => 38221, 'en' => 41134, 'default' => 38221 ], [ '客戶案例', 'case-studies' ], 'zh-hant' ),
        '藝術工藝'      => cc_resolve_page_url( [ 'zh-hant' => 38310, 'en' => 41110, 'default' => 38310 ], [ '藝術工藝', 'art-craft' ], 'zh-hant' ),
        '聯絡我們'      => cc_contact_page_url( 'zh-hant' ),
        'EN'           => $home_en,
    ];

    foreach ( $items as $item ) {
        if ( ! isset( $item->title ) ) {
            continue;
        }

        $id    = isset( $item->ID ) ? (int) $item->ID : 0;
        $title = trim( wp_strip_all_tags( (string) $item->title ) );

        $id_map = [
            41545 => $home_zh,
            41546 => cc_shop_page_url( 'zh-hant' ),
            41550 => cc_contact_page_url( 'zh-hant' ),
            41551 => $home_en,
            41552 => $home_en,
            41553 => cc_shop_page_url( 'en' ),
            41557 => cc_contact_page_url( 'en' ),
            41558 => $home_zh,
        ];

        if ( isset( $id_map[ $id ] ) && is_string( $id_map[ $id ] ) && $id_map[ $id ] !== '' ) {
            $item->url = $id_map[ $id ];
            continue;
        }

        if ( isset( $map[ $title ] ) && is_string( $map[ $title ] ) && $map[ $title ] !== '' ) {
            $item->url = $map[ $title ];
        }
    }

    return $items;
}, 20 );

// 8. Category grid shortcode — bypasses WPML's Elementor widget processing
add_shortcode( 'cc_catgrid', function( $atts ) {
    $atts = shortcode_atts( [ 'lang' => '' ], $atts );

    $lang = $atts['lang'] ? sanitize_key( $atts['lang'] ) : cc_current_lang();

    // Category slug → EN/ZH pairs: [en_slug, zh_slug, EN label, ZH label, EN term_id, ZH term_id]
    $cats = [
        [ 'trophy',              '獎盃',     'Trophy',              '獎盃',     347, 333 ],
        [ 'medal',               '獎牌',     'Medal',               '獎牌',     346, 332 ],
        [ 'crystal-trophy',      '水晶獎座', 'Crystal Trophy',      '水晶獎座', 345, 331 ],
        [ 'plaque',              '木盾',     'Plaque',              '木盾',     344, 339 ],
        [ 'commemorative-plate', '銀碟',     'Commemorative Plate', '銀碟',     350, 335 ],
        [ 'certificate',         '證書',     'Certificate',         '證書',     349, 338 ],
        [ 'flag',                '旗幟',     'Flag',                '旗幟',     343, 334 ],
        [ 'commemorative-gift',  '廣告禮品', 'Promotional Gift',    '廣告禮品', 342, 340 ],
    ];

    $is_en   = ( $lang === 'en' );
    $html    = '';

    foreach ( $cats as $c ) {
        $label = $is_en ? $c[2] : $c[3];
        $term_id   = $is_en ? $c[4] : $c[5];
        $url       = cc_product_category_url( $term_id, $lang );
        $thumb_id  = (int) get_term_meta( $term_id, 'thumbnail_id', true );
        $thumb_url = cc_fallback_media_url_for_term( $term_id );

        if ( ! $thumb_url ) {
            $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : wc_placeholder_img_src( 'medium' );
        }

        $html .= '<div class="elementor-element elementor-widget elementor-widget-image-box cc-catcard">';
        $html .= '<div class="elementor-widget-container">';
        $html .= '<div class="elementor-image-box-wrapper">';
        $html .= '<figure class="elementor-image-box-img">';
        $html .= '<a href="' . esc_url( $url ) . '" tabindex="-1">';
        $html .= '<img src="' . esc_url( $thumb_url ) . '" alt="' . esc_attr( $label ) . '" loading="lazy" width="300" height="300">';
        $html .= '</a></figure>';
        $html .= '<div class="elementor-image-box-content">';
        $html .= '<h3 class="elementor-image-box-title"><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></h3>';
        $html .= '</div></div></div></div>';
    }

    return $html;
} );

add_shortcode( 'cc_featured_products', function( $atts ) {
    $atts = shortcode_atts( [ 'lang' => '' ], $atts );
    $lang = $atts['lang'] ? sanitize_key( $atts['lang'] ) : cc_current_lang();
    $ids  = array_filter( array_map( 'intval', cc_featured_product_ids( $lang ) ) );

    if ( empty( $ids ) ) {
        return '';
    }

    return do_shortcode(
        sprintf(
            '[products ids="%s" columns="4" orderby="post__in"]',
            esc_attr( implode( ',', $ids ) )
        )
    );
} );

function cc_page_image_html( $attachment_id, $size, $alt ) {
    $attachment_id = (int) $attachment_id;
    $src           = $attachment_id ? wp_get_attachment_image_url( $attachment_id, $size ) : '';

    if ( ! $src ) {
        return '';
    }

    return sprintf(
        '<img src="%1$s" alt="%2$s" loading="lazy" decoding="async" />',
        esc_url( $src ),
        esc_attr( $alt )
    );
}

function cc_page_action_link( $label, $url, $variant = 'primary' ) {
    return sprintf(
        '<a class="cc-page-button cc-page-button--%1$s" href="%2$s">%3$s</a>',
        esc_attr( $variant ),
        esc_url( $url ),
        esc_html( $label )
    );
}

add_shortcode( 'cc_about_page', function () {
    $is_en = cc_is_en();

    $copy = $is_en
        ? [
            'eyebrow' => 'ABOUT PREMIER TROPHY',
            'title'   => 'Custom awards and presentation pieces built for real events.',
            'lead'    => 'Premier Trophy supports schools, institutions, associations and corporate teams across Hong Kong with a cleaner, more dependable custom-award workflow.',
            'intro'   => [
                'We focus on trophies, medals, certificates, plaques, crystal awards, flags and presentation gifts that need to look credible on stage, on camera and in hand.',
                'The work is practical: align the format to the occasion, lock the artwork, confirm the production path and deliver pieces that feel appropriate to the audience and budget.',
            ],
            'cards'   => [
                [
                    'title' => 'What we make',
                    'body'  => 'Trophies, medals, plaques, certificates, crystal awards, flags and commemorative or promotional presentation gifts.',
                ],
                [
                    'title' => 'Who we support',
                    'body'  => 'Schools, universities, institutions, associations, sports events, ceremonies and corporate recognition programmes.',
                ],
                [
                    'title' => 'How we work',
                    'body'  => 'Clear quotation guidance, practical recommendations, artwork coordination and dependable delivery planning.',
                ],
            ],
            'section_title' => 'Why clients come to us',
            'bullets'       => [
                'Because the brief is usually time-sensitive and public-facing, not theoretical.',
                'Because event organisers need options that look credible without overcomplicating the process.',
                'Because schools and institutions often need repeatable formats they can trust across multiple ceremonies.',
                'Because presentation quality matters as much as the wording engraved on the piece itself.',
            ],
            'cta_title' => 'Ready to plan your next award order?',
            'cta_body'  => 'Browse the core product categories or send a brief to our team and we will help you narrow the right format quickly.',
            'shop'      => 'Browse Products',
            'contact'   => 'Contact Us',
        ]
        : [
            'eyebrow' => '關於 PREMIER TROPHY',
            'title'   => '為真實活動場景打造更穩妥、更體面的訂製獎品方案。',
            'lead'    => 'Premier Trophy 主要為香港學校、機構、協會及企業活動提供獎盃、獎牌、證書、木盾、水晶獎座及展示禮品訂製。',
            'intro'   => [
                '我們重視的不只是「有產品」，而是讓最終成品在頒獎台、典禮現場、相片畫面與收件者手上都看起來得體、可靠、合乎場合。',
                '整個流程以實際落地為主：先釐清用途與形象，再確認款式、稿件、數量與交期，讓客戶在有限時間內作出穩妥選擇。',
            ],
            'cards'   => [
                [
                    'title' => '產品範圍',
                    'body'  => '獎盃、獎牌、木盾、證書、水晶獎座、旗幟，以及各類紀念及企業禮品。',
                ],
                [
                    'title' => '服務對象',
                    'body'  => '學校、大專院校、機構、協會、企業、體育活動及正式典禮。',
                ],
                [
                    'title' => '合作方式',
                    'body'  => '清晰報價、實際建議、稿件配合及交期規劃，減少來回摸索與風險。',
                ],
            ],
            'section_title' => '客戶選擇我們的原因',
            'bullets'       => [
                '因為大部分訂單都與典禮、比賽或公開活動有關，不能只看便宜或快，而要看是否合乎場合。',
                '因為主辦方往往需要在有限時間內做出可信、得體而不失效率的決定。',
                '因為學校及機構常常需要可重複沿用的獎項方案，而不是每次重新摸索。',
                '因為成品質感與展示效果，往往和刻字內容一樣重要。',
            ],
            'cta_title' => '準備好規劃下一批獎項了嗎？',
            'cta_body'  => '可先瀏覽主要產品分類，或直接提交查詢，我們會協助您快速收窄適合的方案。',
            'shop'      => '瀏覽產品',
            'contact'   => '聯絡我們',
        ];

    ob_start();
    ?>
    <section class="cc-page-shell">
      <div class="cc-page-hero">
        <p class="cc-page-eyebrow"><?php echo esc_html( $copy['eyebrow'] ); ?></p>
        <h1><?php echo esc_html( $copy['title'] ); ?></h1>
        <p class="cc-page-lead"><?php echo esc_html( $copy['lead'] ); ?></p>
      </div>
      <div class="cc-page-section cc-page-prose">
        <?php foreach ( $copy['intro'] as $paragraph ) : ?>
          <p><?php echo esc_html( $paragraph ); ?></p>
        <?php endforeach; ?>
      </div>
      <div class="cc-page-grid cc-page-grid--3">
        <?php foreach ( $copy['cards'] as $card ) : ?>
          <article class="cc-page-card">
            <h2><?php echo esc_html( $card['title'] ); ?></h2>
            <p><?php echo esc_html( $card['body'] ); ?></p>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="cc-page-section">
        <h2><?php echo esc_html( $copy['section_title'] ); ?></h2>
        <ul class="cc-page-list">
          <?php foreach ( $copy['bullets'] as $bullet ) : ?>
            <li><?php echo esc_html( $bullet ); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="cc-page-cta">
        <h2><?php echo esc_html( $copy['cta_title'] ); ?></h2>
        <p><?php echo esc_html( $copy['cta_body'] ); ?></p>
        <div class="cc-page-actions">
          <?php echo cc_page_action_link( $copy['shop'], cc_shop_page_url( cc_current_lang() ) ); ?>
          <?php echo cc_page_action_link( $copy['contact'], cc_contact_page_url( cc_current_lang() ), 'secondary' ); ?>
        </div>
      </div>
    </section>
    <?php
    return (string) ob_get_clean();
} );

add_shortcode( 'cc_cases_page', function () {
    $is_en = cc_is_en();

    $cases = $is_en
        ? [
            [
                'title' => 'Standard Chartered Bank',
                'meta'  => 'Corporate awards ceremony | Trophies',
                'body'  => 'A presentation set produced for an annual recognition event, with engraved award titles and a finish suitable for a formal corporate stage.',
                'image' => 37591,
            ],
            [
                'title' => 'Miss Asia Pageant',
                'meta'  => 'Pageant event | Trophies and presentation gifts',
                'body'  => 'Custom award pieces created to reflect the event identity and the more polished visual standard expected in a media-facing ceremony.',
                'image' => 37602,
            ],
            [
                'title' => 'Corporate Clients',
                'meta'  => 'Recognition programmes | Trophies, medals, plaques',
                'body'  => 'Recurring projects for organisations that need dependable production across anniversary gifts, team recognition and presentation occasions.',
                'image' => 38859,
            ],
            [
                'title' => 'Sports & Athletic Events',
                'meta'  => 'Competition events | Medals and trophies',
                'body'  => 'Bulk award production for sports days, tournaments and regional events where timing, consistency and presentation quality all matter.',
                'image' => 38863,
            ],
        ]
        : [
            [
                'title' => '渣打銀行',
                'meta'  => '企業頒獎典禮｜獎盃',
                'body'  => '為年度表揚典禮製作的一系列獎盃，配合企業品牌與正式頒獎場合，整體效果偏向穩重、專業及上鏡。',
                'image' => 37591,
            ],
            [
                'title' => '亞洲小姐',
                'meta'  => '選美活動｜獎盃及展示禮品',
                'body'  => '按活動形象訂製的獎項與展示禮品，重點在於儀式感、品牌一致性，以及現場展示時的整體質感。',
                'image' => 37602,
            ],
            [
                'title' => '企業客戶',
                'meta'  => '表揚及紀念項目｜獎盃、獎牌、木盾',
                'body'  => '涵蓋企業周年紀念、員工表揚及活動獎項等不同場景，重視穩定交付與成品呈現的一致性。',
                'image' => 38859,
            ],
            [
                'title' => '體育及比賽活動',
                'meta'  => '賽事頒獎｜獎牌及獎盃',
                'body'  => '為多個體育活動與比賽提供批量獎牌及獎盃，重點在交期管理、數量準確與頒獎現場的展示效果。',
                'image' => 38863,
            ],
        ];

    $copy = $is_en
        ? [
            'eyebrow' => 'CASE STUDIES',
            'title'   => 'Selected projects from ceremonies, schools and institutional events.',
            'lead'    => 'A few representative examples of the kinds of award and presentation work Premier Trophy supports in Hong Kong.',
            'cta_title' => 'Need a proposal for your own event?',
            'cta_body'  => 'Tell us the occasion, quantity and timing. We will help you narrow down a format that fits the event properly.',
            'contact'   => 'Contact Us',
        ]
        : [
            'eyebrow' => '客戶案例',
            'title'   => '來自典禮、學校及機構活動的實際訂製案例。',
            'lead'    => '以下是 Premier Trophy 曾配合的部分項目，展示不同場合下的獎項與展示禮品應用方式。',
            'cta_title' => '想為您的活動規劃合適方案？',
            'cta_body'  => '只要提供場合、數量及時間，我們便可協助您快速收窄適合的產品與做法。',
            'contact'   => '聯絡我們',
        ];

    ob_start();
    ?>
    <section class="cc-page-shell">
      <div class="cc-page-hero">
        <p class="cc-page-eyebrow"><?php echo esc_html( $copy['eyebrow'] ); ?></p>
        <h1><?php echo esc_html( $copy['title'] ); ?></h1>
        <p class="cc-page-lead"><?php echo esc_html( $copy['lead'] ); ?></p>
      </div>
      <div class="cc-page-grid cc-page-grid--2">
        <?php foreach ( $cases as $case ) : ?>
          <article class="cc-page-card cc-page-card--media">
            <?php echo cc_page_image_html( $case['image'], 'large', $case['title'] ); ?>
            <div class="cc-page-card__body">
              <h2><?php echo esc_html( $case['title'] ); ?></h2>
              <p class="cc-page-meta"><?php echo esc_html( $case['meta'] ); ?></p>
              <p><?php echo esc_html( $case['body'] ); ?></p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="cc-page-cta">
        <h2><?php echo esc_html( $copy['cta_title'] ); ?></h2>
        <p><?php echo esc_html( $copy['cta_body'] ); ?></p>
        <div class="cc-page-actions">
          <?php echo cc_page_action_link( $copy['contact'], cc_contact_page_url( cc_current_lang() ) ); ?>
        </div>
      </div>
    </section>
    <?php
    return (string) ob_get_clean();
} );

add_shortcode( 'cc_art_page', function () {
    $is_en = cc_is_en();

    $copy = $is_en
        ? [
            'eyebrow' => 'ART & CRAFT',
            'title'   => 'Presentation pieces that sit between gifting, display and ceremony.',
            'lead'    => 'Not every project fits a standard trophy or medal. Some occasions call for a more display-led or commemorative format.',
            'paragraphs' => [
                'Premier Trophy supports those projects with art-and-craft style presentation pieces that feel more bespoke, more decorative or more appropriate for guest-of-honour gifting.',
                'These items are useful when the objective is not just recognition, but also symbolism, display value and long-term commemorative presence.',
            ],
            'uses_title' => 'Common uses',
            'uses'       => [
                'Guest-of-honour and protocol gifts',
                'Anniversary commemorations and institutional milestones',
                'Display pieces for ceremonies, offices and reception areas',
                'Presentation gifts that need stronger visual presence than a standard souvenir',
            ],
            'cta_title' => 'Need something more tailored than a standard award?',
            'cta_body'  => 'Send us the event context and intended audience. We will suggest a presentation format that fits the occasion.',
            'contact'   => 'Discuss Your Project',
        ]
        : [
            'eyebrow' => '藝術工藝',
            'title'   => '介乎禮品、展示與典禮用途之間的訂製展示作品。',
            'lead'    => '並非每個項目都適合標準獎盃或獎牌。有些場合更適合具展示感、紀念性或工藝質感的作品。',
            'paragraphs' => [
                'Premier Trophy 會按場合與對象，協助客戶規劃更具紀念價值或展示效果的藝術工藝及典禮禮品。',
                '這類作品的重點不只是「送出」，而是讓它在展示、收藏及典禮照片中都保持應有的分量與質感。',
            ],
            'uses_title' => '常見用途',
            'uses'       => [
                '主禮嘉賓及貴賓致送禮品',
                '周年紀念、里程碑及機構紀念項目',
                '典禮、辦公室及接待區展示作品',
                '需要較一般紀念品更有份量的展示禮品',
            ],
            'cta_title' => '需要比標準獎項更度身訂造的方案？',
            'cta_body'  => '只要告知場合與對象，我們可協助您建議更合適的展示或禮贈形式。',
            'contact'   => '聯絡我們',
        ];

    ob_start();
    ?>
    <section class="cc-page-shell">
      <div class="cc-page-hero">
        <p class="cc-page-eyebrow"><?php echo esc_html( $copy['eyebrow'] ); ?></p>
        <h1><?php echo esc_html( $copy['title'] ); ?></h1>
        <p class="cc-page-lead"><?php echo esc_html( $copy['lead'] ); ?></p>
      </div>
      <div class="cc-page-feature">
        <div class="cc-page-feature__media">
          <?php echo cc_page_image_html( 38900, 'large', $copy['title'] ); ?>
        </div>
        <div class="cc-page-feature__body">
          <?php foreach ( $copy['paragraphs'] as $paragraph ) : ?>
            <p><?php echo esc_html( $paragraph ); ?></p>
          <?php endforeach; ?>
          <h2><?php echo esc_html( $copy['uses_title'] ); ?></h2>
          <ul class="cc-page-list">
            <?php foreach ( $copy['uses'] as $item ) : ?>
              <li><?php echo esc_html( $item ); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <div class="cc-page-cta">
        <h2><?php echo esc_html( $copy['cta_title'] ); ?></h2>
        <p><?php echo esc_html( $copy['cta_body'] ); ?></p>
        <div class="cc-page-actions">
          <?php echo cc_page_action_link( $copy['contact'], cc_contact_page_url( cc_current_lang() ) ); ?>
        </div>
      </div>
    </section>
    <?php
    return (string) ob_get_clean();
} );

function cc_filter_frontend_markup( $html ) {
    if ( ! is_string( $html ) || $html === '' ) {
        return $html;
    }

    $phone_text = cc_is_en() ? 'Quote Hotline 2151 3944' : '報價直線 2151 3944';
    $help_text  = cc_is_en() ? 'Quick Contact' : '快速聯絡';
    $call_text  = cc_is_en() ? 'Call Us' : '立即致電';

    $html = str_replace( 'Crystal Century', 'Premier Trophy', $html );
    $html = preg_replace( '#<div role="navigation" aria-label="(?:Language Switcher|語言切換器)".*?</div>#s', '', $html );
    $html = preg_replace( '#<div class="otgs-development-site-front-end">.*?</div\s*>#s', '', $html );
    $html = preg_replace( '#<a[^>]+href="https://wa\.me/\+85291055996"[^>]*>.*?</a>#s', '', $html );

    $html = str_replace( [ 'href="tel:+852 91055996"', "href='tel:+852 91055996'" ], [ 'href="tel:+85221513944"', "href='tel:+85221513944'" ], $html );
    $html = str_replace( [ 'href="tel:+85291055996"', "href='tel:+85291055996'" ], [ 'href="tel:+85221513944"', "href='tel:+85221513944'" ], $html );
    $html = str_replace( '報價直線21513944', $phone_text, $html );
    $html = str_replace( 'Need help?', $help_text, $html );
    $html = str_replace( 'Let’s talk ', $call_text, $html );
    $html = str_replace(
        'id="elementor-menu-cart__toggle_button" href="#"',
        'id="elementor-menu-cart__toggle_button" href="' . esc_url( wc_get_cart_url() ) . '"',
        $html
    );

    if ( is_shop() ) {
        if ( cc_is_en() ) {
            $html = cc_rewrite_shop_category_card( $html, 'commemorative-gift', 'Promotional Gift' );
            $html = cc_rewrite_shop_category_card( $html, 'pin', 'Pin Badge' );
            $html = cc_rewrite_shop_category_card( $html, 'medal', 'Medal', get_stylesheet_directory_uri() . '/assets/category-medal.svg' );
            $html = cc_rewrite_shop_category_card( $html, 'commemorative-plate', 'Commemorative Plate', get_stylesheet_directory_uri() . '/assets/category-commemorative-plate.svg' );
        } else {
            $html = cc_rewrite_shop_category_card( $html, 'medal', '獎牌', get_stylesheet_directory_uri() . '/assets/category-medal.svg' );
            $html = cc_rewrite_shop_category_card( $html, 'commemorative-plate', '銀碟', get_stylesheet_directory_uri() . '/assets/category-commemorative-plate.svg' );
        }
    }

    if ( cc_is_en() ) {
        $html = str_replace( [ 'href="https://www.crystalcentury.com/shop/"', "href='https://www.crystalcentury.com/shop/'" ], [ 'href="https://www.crystalcentury.com/shop/?lang=en"', "href='https://www.crystalcentury.com/shop/?lang=en'" ], $html );
        $html = str_replace( 'aria-label="選單"', 'aria-label="Menu"', $html );

        $replacements = [
            '姓名' => 'Name',
            '公司名稱' => 'Company name',
            '電話' => 'Phone',
            '電子郵件' => 'Email',
            '訂購的產品' => 'Selected product',
            '数量' => 'Quantity',
            '數量' => 'Quantity',
            '尺寸' => 'Size',
            '預計收貨日期' => 'Requested delivery date',
            '取貨方式' => 'Collection method',
            '自取' => 'Self pickup',
            '送貨' => 'Delivery',
            '查詢内容' => 'Enquiry details',
            '備註(如有)' => 'Notes (optional)',
            '產品 ID' => 'Selected product',
            '自訂高度' => 'Custom height',
            '查詢' => 'Submit enquiry',
            'Visit product category Commemorative Gift' => 'Visit product category Promotional Gift',
            'Commemorative Gift (' => 'Promotional Gift (',
            '>Commemorative Gift<' => '>Promotional Gift<',
            'alt="Commemorative Gift"' => 'alt="Promotional Gift"',
            'Visit product category Pin' => 'Visit product category Pin Badge',
            '>Pin<' => '>Pin Badge<',
            'alt="Pin"' => 'alt="Pin Badge"',
        ];

        $html = str_replace( array_keys( $replacements ), array_values( $replacements ), $html );

        if ( is_shop() ) {
            $html = cc_fix_shop_hreflang_links( $html );
        }
    }

    if ( is_page( [ 38309, 41116 ] ) ) {
        $html = preg_replace( '/<h2([^>]*)>(Contact Us|聯絡我們)<\/h2>/', '<h1$1>$2</h1>', $html, 1 );
    }

    if ( is_cart() ) {
        $html = str_replace( 'Cart - Premier Trophy', 'Quote List | Premier Trophy Hong Kong', $html );
    }

    return $html;
}

function cc_rewrite_shop_category_card( $html, $slug, $label, $image_url = '' ) {
    $pattern = '#(<a[^>]+href="https://www\.crystalcentury\.com/product-category/' . preg_quote( $slug, '#' ) . '/(?:\?lang=en)?"[^>]*>)(.*?)(<h2 class="woocommerce-loop-category__title">\s*)(.*?)(\s*</h2>)#s';

    return preg_replace_callback(
        $pattern,
        static function ( $matches ) use ( $label, $image_url ) {
            $anchor = $matches[1] . $matches[2];
            $anchor = preg_replace( '#aria-label="[^"]*"#', 'aria-label="Visit product category ' . esc_attr( $label ) . '"', $anchor, 1 );
            $anchor = preg_replace( '#alt="[^"]*"#', 'alt="' . esc_attr( $label ) . '"', $anchor, 1 );

            if ( $image_url ) {
                $asset_url = esc_url( $image_url );
                $anchor    = preg_replace( '#data-src="[^"]*"#', 'data-src="' . $asset_url . '"', $anchor, 1 );
                $anchor    = preg_replace( '#data-srcset="[^"]*"#', 'data-srcset="' . $asset_url . ' 300w"', $anchor, 1 );
                $anchor    = preg_replace( '#src="[^"]*"#', 'src="' . $asset_url . '"', $anchor, 1 );
                $anchor    = preg_replace( '#srcset="[^"]*"#', 'srcset="' . $asset_url . ' 300w"', $anchor, 1 );
            }

            return $anchor . $matches[3] . esc_html( $label ) . $matches[5];
        },
        $html
    );
}

function cc_fix_shop_hreflang_links( $html ) {
    $html = str_replace(
        'hreflang="zh-hant" href="https://www.crystalcentury.com/shop/?lang=en"',
        'hreflang="zh-hant" href="https://www.crystalcentury.com/shop/"',
        $html
    );
    $html = str_replace(
        'hreflang="en" href="https://www.crystalcentury.com/shop/?lang=en"',
        'hreflang="en" href="https://www.crystalcentury.com/shop-en/?lang=en"',
        $html
    );
    $html = str_replace(
        'hreflang="x-default" href="https://www.crystalcentury.com/shop/?lang=en"',
        'hreflang="x-default" href="https://www.crystalcentury.com/shop/"',
        $html
    );

    return $html;
}

add_action( 'template_redirect', function () {
    if ( is_admin() || wp_doing_ajax() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }

    ob_start( 'cc_filter_frontend_markup' );
}, 0 );
