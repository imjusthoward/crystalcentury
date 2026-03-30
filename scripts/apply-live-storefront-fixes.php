<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$backup_dir = WP_CONTENT_DIR . '/uploads/cc-backups/' . gmdate( 'Ymd-His' );
wp_mkdir_p( $backup_dir );

function cc_backup_payload( $backup_dir, $filename, $payload ) {
	file_put_contents(
		trailingslashit( $backup_dir ) . $filename,
		wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
	);
}

function cc_decode_u_sequences_for_storefront( $content ) {
	if ( ! is_string( $content ) || ! preg_match( '/(?:u[0-9a-fA-F]{4}){2,}/', $content ) ) {
		return $content;
	}

	return preg_replace_callback(
		'/(?:u[0-9a-fA-F]{4})+/',
		static function ( $matches ) {
			$json    = '"' . preg_replace( '/u([0-9a-fA-F]{4})/', '\\\\u$1', $matches[0] ) . '"';
			$decoded = json_decode( $json );

			return is_string( $decoded ) ? $decoded : $matches[0];
		},
		$content
	);
}

function cc_walk_elementor_nodes( array &$nodes, callable $callback ) {
	foreach ( $nodes as &$node ) {
		$callback( $node );

		if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
			cc_walk_elementor_nodes( $node['elements'], $callback );
		}
	}
}

function cc_shop_url_for_lang( $lang ) {
	$home_parts = wp_parse_url( home_url( '/' ) );
	$scheme     = $home_parts['scheme'] ?? 'https';
	$host       = $home_parts['host'] ?? '';
	$port       = isset( $home_parts['port'] ) ? ':' . $home_parts['port'] : '';
	$base_path  = isset( $home_parts['path'] ) ? rtrim( (string) $home_parts['path'], '/' ) : '';
	$url        = $scheme . '://' . $host . $port . $base_path . '/shop/';

	if ( $lang === 'en' ) {
		$url = add_query_arg( 'lang', 'en', $url );
	}

	return $url;
}

function cc_homepage_copy_for_lang( $lang ) {
	if ( $lang === 'en' ) {
		return [
			'hero_title'      => 'Custom Trophies<br>Medals &amp; Corporate Gifts',
			'hero_subtitle'   => '<p style="color:#fff;font-size:16px;text-shadow:0 1px 3px rgba(0,0,0,0.5);">One-stop award customisation service for Hong Kong schools, institutions and events.</p>',
			'hero_button'     => 'Browse Products',
			'category_title'  => 'Featured Categories',
			'products_title'  => 'Featured Products',
			'products_widget' => '[cc_featured_products]',
		];
	}

	return [
		'hero_title'      => '精製獎盃<br>獎牌・企業禮品',
		'hero_subtitle'   => '<p style="color:#fff;font-size:16px;text-shadow:0 1px 3px rgba(0,0,0,0.5);">一站式獎項訂製服務 ｜ 香港本地廠商</p>',
		'hero_button'     => '瀏覽全部產品',
		'category_title'  => '精選產品分類',
		'products_title'  => '精選產品',
		'products_widget' => '[cc_featured_products]',
	];
}

function cc_force_product_cat_term( $post_id, $term_id ) {
	global $wpdb;

	$post_id         = (int) $post_id;
	$term_id         = (int) $term_id;
	$term_taxonomy_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'product_cat' LIMIT 1",
			$term_id
		)
	);

	if ( ! $post_id || ! $term_taxonomy_id ) {
		return false;
	}

	$product_cat_tt_ids = $wpdb->get_col( "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'product_cat'" );

	if ( ! empty( $product_cat_tt_ids ) ) {
		$wpdb->query(
			"DELETE FROM {$wpdb->term_relationships} WHERE object_id = {$post_id} AND term_taxonomy_id IN (" . implode( ',', array_map( 'intval', $product_cat_tt_ids ) ) . ')'
		);
	}

	$wpdb->replace(
		$wpdb->term_relationships,
		[
			'object_id'        => $post_id,
			'term_taxonomy_id' => $term_taxonomy_id,
			'term_order'       => 0,
		],
		[
			'%d',
			'%d',
			'%d',
		]
	);

	wp_update_term_count_now( [ $term_taxonomy_id ], 'product_cat' );

	return true;
}

function cc_en_product_canonical( $category_slug, $product_slug ) {
	return add_query_arg(
		'lang',
		'en',
		home_url( '/products/' . trim( $category_slug, '/' ) . '/' . trim( $product_slug, '/' ) . '/' )
	);
}

function cc_build_en_product_map() {
	return [
		39448 => [
			'title'       => 'Pin Badge',
			'slug'        => 'pin-badge',
			'category_id' => 348,
			'category'    => 'pin',
			'keyword'     => 'custom pin badge hong kong',
			'yoast_title' => 'Pin Badge | Premier Trophy Hong Kong',
			'metadesc'    => 'Custom pin badges for schools, clubs, institutions and branded events in Hong Kong. Enquire with Premier Trophy for tailored badge production.',
			'excerpt'     => 'Custom pin badges for schools, associations, ceremonies and brand campaigns.',
			'content'     => '<p>Premier Trophy supplies custom pin badges for schools, institutions, associations and corporate events in Hong Kong. Badge styles can be adapted to suit award presentations, commemorative campaigns, membership programmes and branded giveaways.</p><p><strong>Suitable for:</strong></p><ul><li>School houses, prefect teams and student leadership groups</li><li>Association memberships and anniversary programmes</li><li>Corporate branding, campaigns and event souvenirs</li><li>Recognition items for ceremonies and milestone events</li></ul><p>We support material selection, logo application, sizing and production planning so each badge fits the intended use and presentation standard.</p>',
		],
		39452 => [
			'title'       => 'Trophy',
			'slug'        => 'trophy',
			'category_id' => 347,
			'category'    => 'trophy',
			'keyword'     => 'custom trophy hong kong',
			'yoast_title' => 'Trophy | Premier Trophy Hong Kong',
			'metadesc'    => 'Custom trophy production for schools, institutions, sports events and corporate award ceremonies in Hong Kong. Request a quote from Premier Trophy.',
			'excerpt'     => 'Custom trophies for schools, institutions, sports events and corporate awards.',
			'content'     => '<p>Premier Trophy provides custom trophies for school competitions, sports events, institutional recognition and corporate award ceremonies in Hong Kong. From classic metal styles to modern presentation pieces, each trophy can be tailored to the occasion.</p><p><strong>Suitable for:</strong></p><ul><li>School sports days and academic prize presentations</li><li>Corporate annual awards and gala events</li><li>League, club and tournament prizegiving</li><li>Community, charity and association recognition programmes</li></ul><p>We support engraving, logo placement, event naming and presentation planning from concept approval through final production.</p>',
		],
		39456 => [
			'title'       => 'Medal',
			'slug'        => 'medal',
			'category_id' => 346,
			'category'    => 'medal',
			'keyword'     => 'custom medal hong kong',
			'yoast_title' => 'Medal | Premier Trophy Hong Kong',
			'metadesc'    => 'Order custom medals for school competitions, sports days, races and institutional events in Hong Kong. Ribbon, logo and engraving options available.',
			'excerpt'     => 'Custom medals for competitions, races, ceremonies and event recognition.',
			'content'     => '<p>Premier Trophy produces custom medals for school events, races, tournaments and institutional ceremonies in Hong Kong. Medal designs can be adapted for sport, achievement, participation and commemorative use.</p><p><strong>Suitable for:</strong></p><ul><li>School sports days and inter-school competitions</li><li>Running events, tournaments and open competitions</li><li>Institutional ceremonies and milestone programmes</li><li>Participation awards and commemorative distributions</li></ul><p>We provide support for medal sizing, finish selection, ribbon pairing and branded artwork so the final piece suits both the event and the audience.</p>',
		],
		39460 => [
			'title'       => 'Plaque',
			'slug'        => 'plaque',
			'category_id' => 344,
			'category'    => 'plaque',
			'keyword'     => 'custom plaque hong kong',
			'yoast_title' => 'Plaque | Premier Trophy Hong Kong',
			'metadesc'    => 'Custom plaques for corporate honours, retirement tributes, school recognition and commemorative presentations in Hong Kong.',
			'excerpt'     => 'Custom plaques for formal recognition, presentation and commemorative use.',
			'content'     => '<p>Premier Trophy supplies custom plaques for organisations that need a more formal presentation format than a standard trophy. Plaques are widely used for corporate honours, retirement tributes, school recognition and commemorative presentations in Hong Kong.</p><p><strong>Suitable for:</strong></p><ul><li>Corporate service awards and retirement presentations</li><li>School appreciation gifts and honorary recognition</li><li>Association and institutional milestone commemorations</li><li>Formal presentation items for VIP guests and partners</li></ul><p>We support layout planning, plate engraving, logo placement and wording review so the finished plaque feels polished and presentation-ready.</p>',
		],
		39464 => [
			'title'       => 'Crystal Trophy',
			'slug'        => 'crystal-trophy',
			'category_id' => 345,
			'category'    => 'crystal-trophy',
			'keyword'     => 'custom crystal trophy hong kong',
			'yoast_title' => 'Crystal Trophy | Premier Trophy Hong Kong',
			'metadesc'    => 'Premium crystal trophies and awards for executive recognition, institutional honours and formal ceremonies in Hong Kong.',
			'excerpt'     => 'Premium crystal trophies for executive, institutional and ceremonial recognition.',
			'content'     => '<p>Premier Trophy offers premium crystal trophies for organisations that want a refined and contemporary presentation standard. Crystal awards are ideal when clarity, weight and elegance matter as much as the wording on the piece itself.</p><p><strong>Suitable for:</strong></p><ul><li>Executive recognition and corporate honours</li><li>Institutional ceremonies and board presentations</li><li>High-value sponsorship, donor or partner appreciation</li><li>Formal events requiring a premium award format</li></ul><p>We can advise on shape, base style, engraving layout and branding treatment so the final award feels distinctive and appropriate to the occasion.</p>',
		],
		39468 => [
			'title'       => 'Commemorative Plate',
			'slug'        => 'commemorative-plate',
			'category_id' => 350,
			'category'    => 'commemorative-plate',
			'keyword'     => 'commemorative plate hong kong',
			'yoast_title' => 'Commemorative Plate | Premier Trophy Hong Kong',
			'metadesc'    => 'Commemorative plates for anniversaries, guest honours, institutional milestones and ceremonial gifts in Hong Kong.',
			'excerpt'     => 'Commemorative plates for milestone events, guest honours and ceremonial gifting.',
			'content'     => '<p>Premier Trophy produces commemorative plates for milestone events, guest honours and ceremonial gifting in Hong Kong. They are especially suitable for institutions and organisations that want a traditional presentation format with a formal tone.</p><p><strong>Suitable for:</strong></p><ul><li>Anniversary events and official commemorations</li><li>Guest-of-honour presentations and protocol gifting</li><li>Institutional milestones and celebratory ceremonies</li><li>Association or board appreciation pieces</li></ul><p>We provide layout planning, logo placement and inscription guidance so each plate is presentation-ready and appropriate for the event context.</p>',
		],
		39472 => [
			'title'       => 'Flag & Banner',
			'slug'        => 'flag-banner',
			'category_id' => 343,
			'category'    => 'flag',
			'keyword'     => 'custom flag banner hong kong',
			'yoast_title' => 'Flag & Banner | Premier Trophy Hong Kong',
			'metadesc'    => 'Custom flags and banners for schools, ceremonies, team identity, stage presentation and institutional events in Hong Kong.',
			'excerpt'     => 'Custom flags and banners for schools, ceremonies, teams and institutions.',
			'content'     => '<p>Premier Trophy supplies custom flags and banners for schools, institutions, houses, teams and ceremonial events in Hong Kong. These pieces help reinforce identity, visibility and stage presence at official functions and public events.</p><p><strong>Suitable for:</strong></p><ul><li>School houses, teams and ceremonial formations</li><li>Institutional functions and commemorative displays</li><li>Stage presentation, backdrop and processional use</li><li>Association branding and event environments</li></ul><p>We can advise on format, finishing, mounting options and visual hierarchy so the final flag or banner works properly in live event settings.</p>',
		],
		39476 => [
			'title'       => 'Promotional Gift',
			'slug'        => 'promotional-gift',
			'category_id' => 342,
			'category'    => 'commemorative-gift',
			'keyword'     => 'promotional gift hong kong',
			'yoast_title' => 'Promotional Gift | Premier Trophy Hong Kong',
			'metadesc'    => 'Custom promotional gifts for campaigns, corporate events, school programmes and branded distributions in Hong Kong.',
			'excerpt'     => 'Custom promotional gifts for events, campaigns, schools and brand programmes.',
			'content'     => '<p>Premier Trophy provides promotional gifts for organisations that need practical branded items for campaigns, events and stakeholder engagement. These pieces work well when the objective is visibility, memorability and consistent presentation.</p><p><strong>Suitable for:</strong></p><ul><li>Corporate campaigns and event giveaways</li><li>School and institutional programmes</li><li>Conference packs and sponsor distributions</li><li>Community outreach and branded appreciation items</li></ul><p>We can help match the product format, branding treatment and quantity plan to the event purpose and target audience.</p>',
		],
		39480 => [
			'title'       => 'Acrylic Award',
			'slug'        => 'acrylic-award',
			'category_id' => 341,
			'category'    => 'acrylic',
			'keyword'     => 'acrylic award hong kong',
			'yoast_title' => 'Acrylic Award | Premier Trophy Hong Kong',
			'metadesc'    => 'Modern acrylic awards for schools, institutions, business recognition and event presentation in Hong Kong.',
			'excerpt'     => 'Modern acrylic awards for schools, institutions and event recognition.',
			'content'     => '<p>Premier Trophy supplies acrylic awards for organisations looking for a clean, modern presentation format. Acrylic pieces are suitable when you want a polished award with strong visual clarity and flexible branding options.</p><p><strong>Suitable for:</strong></p><ul><li>School recognition and achievement programmes</li><li>Business and institutional awards</li><li>Event prizes and presentation ceremonies</li><li>Modern brand-led recognition formats</li></ul><p>We support sizing, colour treatment, engraving layout and graphic application so the final award feels contemporary and well-resolved.</p>',
		],
		39484 => [
			'title'       => '3D Crystal Award',
			'slug'        => '3d-crystal-award',
			'category_id' => 298,
			'category'    => '3d-crystal',
			'keyword'     => '3d crystal award hong kong',
			'yoast_title' => '3D Crystal Award | Premier Trophy Hong Kong',
			'metadesc'    => '3D crystal awards with internal laser engraving for premium recognition, commemorative gifting and ceremonial presentation in Hong Kong.',
			'excerpt'     => 'Premium 3D crystal awards for executive recognition and commemorative gifting.',
			'content'     => '<p>Premier Trophy offers 3D crystal awards with internal laser engraving for organisations that want a premium and distinctive recognition piece. These awards are especially effective for executive honours, commemorative gifting and formal presentation.</p><p><strong>Suitable for:</strong></p><ul><li>Executive and VIP recognition</li><li>Commemorative and milestone gifting</li><li>Institutional or donor appreciation</li><li>Premium event and ceremonial presentation</li></ul><p>We can advise on shape, internal engraving treatment and presentation style so the award feels high-value and appropriate to the occasion.</p>',
		],
		39545 => [
			'title'       => 'Certificate',
			'slug'        => 'certificate',
			'category_id' => 349,
			'category'    => 'certificate',
			'keyword'     => 'certificate printing hong kong',
			'yoast_title' => 'Certificate | Premier Trophy Hong Kong',
			'metadesc'    => 'Custom certificates for schools, institutions, competitions and formal recognition programmes in Hong Kong.',
			'excerpt'     => 'Custom certificates for schools, institutions, competitions and formal recognition.',
			'content'     => '<p>Premier Trophy supplies custom certificates for schools, institutions, competitions and formal recognition programmes in Hong Kong. Certificates can be produced for academic achievement, participation, appreciation and official presentation use.</p><p><strong>Suitable for:</strong></p><ul><li>School prizegiving and academic recognition</li><li>Training, participation and completion awards</li><li>Corporate appreciation and milestone presentation</li><li>Institutional and association ceremonies</li></ul><p>We provide support on layout, wording, logo placement and finishing so the final certificate matches the tone and standard of the event.</p>',
		],
		39683 => [
			'title'       => 'Certificate (Premium)',
			'slug'        => 'certificate-premium',
			'category_id' => 349,
			'category'    => 'certificate',
			'keyword'     => 'premium certificate hong kong',
			'yoast_title' => 'Certificate (Premium) | Premier Trophy Hong Kong',
			'metadesc'    => 'Premium certificate format for executive presentation, institutional ceremonies and formal recognition in Hong Kong.',
			'excerpt'     => 'Premium certificate format for formal ceremonies and high-value recognition.',
			'content'     => '<p>Premier Trophy offers premium certificate formats for organisations that need a more elevated presentation standard. This style is suited to executive recognition, institutional ceremonies and occasions where the certificate itself must feel substantial.</p><p><strong>Suitable for:</strong></p><ul><li>Executive and VIP recognition</li><li>Institutional ceremonies and formal presentation</li><li>Premium donor, partner or sponsor appreciation</li><li>Milestone and anniversary commemorations</li></ul><p>We can advise on wording hierarchy, emblem placement and finishing choices so the final piece feels formal, credible and well presented.</p>',
		],
		39687 => [
			'title'       => 'Certificate (Wood Frame)',
			'slug'        => 'certificate-wood-frame',
			'category_id' => 349,
			'category'    => 'certificate',
			'keyword'     => 'wood frame certificate hong kong',
			'yoast_title' => 'Certificate (Wood Frame) | Premier Trophy Hong Kong',
			'metadesc'    => 'Wood frame certificate presentation for schools, institutions and commemorative display in Hong Kong.',
			'excerpt'     => 'Wood frame certificate presentation for formal recognition and display.',
			'content'     => '<p>Premier Trophy provides wood frame certificate formats for clients who want a warmer, more traditional presentation style. This option works well when the certificate is intended for display after the ceremony.</p><p><strong>Suitable for:</strong></p><ul><li>School and institutional presentation ceremonies</li><li>Retirement, appreciation and honour displays</li><li>Association recognition and milestone gifting</li><li>Display-ready commemorative presentation</li></ul><p>We can help structure the artwork and layout so the certificate remains readable, balanced and suitable for long-term display.</p>',
		],
		39691 => [
			'title'       => 'Certificate (Metal Frame)',
			'slug'        => 'certificate-metal-frame',
			'category_id' => 349,
			'category'    => 'certificate',
			'keyword'     => 'metal frame certificate hong kong',
			'yoast_title' => 'Certificate (Metal Frame) | Premier Trophy Hong Kong',
			'metadesc'    => 'Metal frame certificate presentation for professional recognition, institutional honours and formal display in Hong Kong.',
			'excerpt'     => 'Metal frame certificate presentation for polished, formal recognition.',
			'content'     => '<p>Premier Trophy supplies metal frame certificate formats for clients who want a cleaner, more contemporary presentation finish. This style suits professional recognition and formal display settings.</p><p><strong>Suitable for:</strong></p><ul><li>Corporate and institutional recognition</li><li>Professional certification and formal presentation</li><li>Executive appreciation and commemorative display</li><li>Events where a modern frame finish is preferred</li></ul><p>We support layout planning and inscription hierarchy so the framed piece remains legible, balanced and appropriate for public presentation.</p>',
		],
		39696 => [
			'title'       => 'Certificate (Plaque Style)',
			'slug'        => 'certificate-plaque-style',
			'category_id' => 349,
			'category'    => 'certificate',
			'keyword'     => 'certificate plaque hong kong',
			'yoast_title' => 'Certificate (Plaque Style) | Premier Trophy Hong Kong',
			'metadesc'    => 'Plaque-style certificate presentation for commemorative honours, appreciation awards and formal display in Hong Kong.',
			'excerpt'     => 'Plaque-style certificate presentation for commemorative and formal display use.',
			'content'     => '<p>Premier Trophy offers plaque-style certificate presentation for recognition that needs the structure of a certificate with the display presence of a plaque. It is a suitable choice for commemorative honours and appreciation awards.</p><p><strong>Suitable for:</strong></p><ul><li>Commemorative honours and milestone recognition</li><li>Institutional appreciation and donor presentation</li><li>Formal display pieces for offices or venues</li><li>Events where a certificate alone feels too light</li></ul><p>We help balance text hierarchy, branding and display format so the finished piece feels formal, durable and visually complete.</p>',
		],
	];
}

function cc_patch_homepage_data( $post_id, $lang ) {
	$json = get_post_meta( $post_id, '_elementor_data', true );
	$data = json_decode( $json, true );

	if ( ! is_array( $data ) ) {
		return false;
	}

	$shop_url = cc_shop_url_for_lang( $lang );
	$copy     = cc_homepage_copy_for_lang( $lang );
	$updated  = false;

	cc_walk_elementor_nodes(
		$data,
		static function ( array &$node ) use ( $shop_url, $copy, &$updated ) {
			$widget_type = $node['widgetType'] ?? '';

			if ( $widget_type === 'image-carousel' && ! empty( $node['settings']['carousel'] ) && is_array( $node['settings']['carousel'] ) ) {
				$node['settings']['thumbnail_size'] = 'full';

				foreach ( $node['settings']['carousel'] as &$image ) {
					if ( empty( $image['id'] ) ) {
						continue;
					}

					$url = wp_get_attachment_url( (int) $image['id'] );
					if ( $url ) {
						$image['url'] = $url;
					}
				}

				$updated = true;
			}

			if ( $widget_type === 'button' ) {
				$link_url = $node['settings']['link']['url'] ?? '';
				if ( ( $node['id'] ?? '' ) === 'cc_hbtn' || preg_match( '#/products/?$#', (string) $link_url ) ) {
					$node['settings']['link']['url'] = $shop_url;
					$node['settings']['text']      = $copy['hero_button'];
					$updated                       = true;
				}
			}

			if ( ( $node['id'] ?? '' ) === 'cc_htag' && $widget_type === 'heading' ) {
				$node['settings']['title'] = $copy['hero_title'];
				$updated                   = true;
			}

			if ( ( $node['id'] ?? '' ) === 'cc_hsub' && $widget_type === 'text-editor' ) {
				$node['settings']['editor'] = $copy['hero_subtitle'];
				$updated                    = true;
			}

			if ( ( $node['id'] ?? '' ) === 'cc_cattitle' && $widget_type === 'heading' ) {
				$node['settings']['title'] = $copy['category_title'];
				$updated                   = true;
			}

			if ( ( $node['id'] ?? '' ) === '7dd359d6' && $widget_type === 'heading' ) {
				$node['settings']['title'] = $copy['products_title'];
				$updated                   = true;
			}

			if ( $widget_type === 'heading' ) {
				$title = (string) ( $node['settings']['title'] ?? '' );
				if ( $title !== '' && ( strpos( $title, 'Top 99' ) !== false || strpos( $title, '人氣熱選' ) !== false ) ) {
					$node['settings']['title'] = $copy['products_title'];
					$updated                   = true;
				}
			}

			if ( $widget_type === 'shortcode' && strpos( (string) ( $node['settings']['shortcode'] ?? '' ), '[products' ) !== false ) {
				$node['settings']['shortcode'] = $copy['products_widget'];
				$updated                       = true;
			}
		}
	);

	if ( ! $updated ) {
		return false;
	}

	update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
	clean_post_cache( $post_id );

	return $json;
}

function cc_normalize_en_products( $backup_dir ) {
	$map     = cc_build_en_product_map();
	$backups = [];

	foreach ( $map as $product_id => $spec ) {
		$product = get_post( $product_id );

		if ( ! $product instanceof WP_Post || $product->post_type !== 'product' ) {
			continue;
		}

		$backups[ $product_id ] = [
			'title'        => $product->post_title,
			'slug'         => $product->post_name,
			'post_content' => $product->post_content,
			'post_excerpt' => $product->post_excerpt,
			'category_ids' => wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'ids' ] ),
			'canonical'    => get_post_meta( $product_id, '_yoast_wpseo_canonical', true ),
			'yoast_title'  => get_post_meta( $product_id, '_yoast_wpseo_title', true ),
			'metadesc'     => get_post_meta( $product_id, '_yoast_wpseo_metadesc', true ),
			'focuskw'      => get_post_meta( $product_id, '_yoast_wpseo_focuskw', true ),
		];

		wp_update_post(
			[
				'ID'           => $product_id,
				'post_title'   => $spec['title'],
				'post_name'    => $spec['slug'],
				'post_content' => $spec['content'],
				'post_excerpt' => $spec['excerpt'],
			]
		);

		cc_force_product_cat_term( $product_id, $spec['category_id'] );

		update_post_meta( $product_id, '_yoast_wpseo_canonical', cc_en_product_canonical( $spec['category'], $spec['slug'] ) );
		update_post_meta( $product_id, '_yoast_wpseo_title', $spec['yoast_title'] );
		update_post_meta( $product_id, '_yoast_wpseo_metadesc', $spec['metadesc'] );
		update_post_meta( $product_id, '_yoast_wpseo_focuskw', $spec['keyword'] );

		clean_post_cache( $product_id );
	}

	if ( $backups ) {
		cc_backup_payload( $backup_dir, 'en-product-normalization-before.json', $backups );
	}

	return count( $backups );
}

function cc_sync_category_thumbnails( $backup_dir ) {
	$map = [
		298 => 40258,
		330 => 40258,
		331 => 38877,
		332 => 40076,
		333 => 38889,
		334 => 40260,
		335 => 40076,
		336 => 38719,
		337 => 40254,
		338 => 38885,
		339 => 38883,
		340 => 40256,
		341 => 40254,
		342 => 40256,
		343 => 40260,
		344 => 38883,
		345 => 38877,
		346 => 40076,
		347 => 38889,
		348 => 38719,
		349 => 38885,
		350 => 40076,
	];

	$backups = [];

	foreach ( $map as $term_id => $attachment_id ) {
		$existing = (int) get_term_meta( $term_id, 'thumbnail_id', true );

		if ( $existing === (int) $attachment_id ) {
			continue;
		}

		$backups[ $term_id ] = $existing;
		update_term_meta( $term_id, 'thumbnail_id', (int) $attachment_id );
	}

	if ( $backups ) {
		cc_backup_payload( $backup_dir, 'category-thumbnails-before.json', $backups );
	}

	return count( $backups );
}

function cc_normalize_en_category_terms( $backup_dir ) {
	$updates = [
		342 => [ 'name' => 'Promotional Gift' ],
		348 => [ 'name' => 'Pin Badge' ],
	];

	$backups = [];

	foreach ( $updates as $term_id => $payload ) {
		$term = get_term( (int) $term_id, 'product_cat' );
		if ( ! $term instanceof WP_Term || is_wp_error( $term ) ) {
			continue;
		}

		$backups[ $term_id ] = [
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
		];

		wp_update_term(
			(int) $term_id,
			'product_cat',
			[
				'name' => $payload['name'],
			]
		);
	}

	if ( $backups ) {
		cc_backup_payload( $backup_dir, 'en-category-terms-before.json', $backups );
	}

	return count( $backups );
}

function cc_patch_broken_catalog_media( $backup_dir ) {
	$placeholder_id = 40076;
	$targets        = [ 39454, 39456, 39466, 39468 ];
	$backups        = [];

	foreach ( $targets as $product_id ) {
		$current_thumb   = (int) get_post_meta( $product_id, '_thumbnail_id', true );
		$current_gallery = (string) get_post_meta( $product_id, '_product_image_gallery', true );

		$backups[ $product_id ] = [
			'_thumbnail_id'         => $current_thumb,
			'_product_image_gallery' => $current_gallery,
		];

		update_post_meta( $product_id, '_thumbnail_id', $placeholder_id );
		update_post_meta( $product_id, '_product_image_gallery', '' );
		clean_post_cache( $product_id );
	}

	if ( $backups ) {
		cc_backup_payload( $backup_dir, 'broken-catalog-media-before.json', $backups );
	}

	return count( $backups );
}

function cc_sync_attachment_alts() {
	$map = [
		38605 => 'Premier Trophy',
		38614 => 'Premier Trophy',
		38719 => 'Custom Pin Badge Hong Kong | Premier Trophy',
		38889 => 'Custom Trophy Hong Kong | Premier Trophy',
		40076 => 'Premier Trophy product image placeholder',
		38883 => 'Custom Plaque Hong Kong | Premier Trophy',
		38877 => 'Custom Crystal Trophy Hong Kong | Premier Trophy',
		40260 => 'Custom Flag and Banner Hong Kong | Premier Trophy',
		40256 => 'Custom Promotional Gift Hong Kong | Premier Trophy',
		40254 => 'Custom Acrylic Award Hong Kong | Premier Trophy',
		40258 => 'Custom 3D Crystal Award Hong Kong | Premier Trophy',
		38885 => 'Custom Certificate Hong Kong | Premier Trophy',
	];

	foreach ( $map as $attachment_id => $alt ) {
		update_post_meta( (int) $attachment_id, '_wp_attachment_image_alt', $alt );
	}
}

function cc_footer_template_payload() {
	return [
		[
			'id'       => 'ccfroot1',
			'elType'   => 'container',
			'isInner'  => false,
			'settings' => [
				'content_width' => 'full',
				'padding'       => [
					'unit'     => 'px',
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'isLinked' => true,
				],
			],
			'elements' => [
				[
					'id'         => 'ccfshort1',
					'elType'     => 'widget',
					'widgetType' => 'shortcode',
					'settings'   => [
						'shortcode' => '[cc_footer]',
					],
					'elements'   => [],
				],
			],
		],
	];
}

function cc_shortcode_page_payload( $shortcode, $prefix ) {
	$prefix = preg_replace( '/[^a-z0-9]/i', '', (string) $prefix );
	$root   = substr( strtolower( $prefix . 'root' ), 0, 8 );
	$short  = substr( strtolower( $prefix . 'short' ), 0, 8 );

	return [
		[
			'id'       => $root,
			'elType'   => 'container',
			'isInner'  => false,
			'settings' => [
				'content_width' => 'full',
				'padding'       => [
					'unit'     => 'px',
					'top'      => '0',
					'right'    => '0',
					'bottom'   => '0',
					'left'     => '0',
					'isLinked' => true,
				],
			],
			'elements' => [
				[
					'id'         => $short,
					'elType'     => 'widget',
					'widgetType' => 'shortcode',
					'settings'   => [
						'shortcode' => $shortcode,
					],
					'elements'   => [],
				],
			],
		],
	];
}

function cc_patch_shortcode_page( $post_id, $shortcode, $prefix ) {
	$previous = get_post_meta( $post_id, '_elementor_data', true );
	$payload  = cc_shortcode_page_payload( $shortcode, $prefix );

	update_post_meta(
		$post_id,
		'_elementor_data',
		wp_slash( wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) )
	);
	update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $post_id, '_wp_page_template', 'default' );
	clean_post_cache( $post_id );

	return $previous;
}

function cc_upsert_footer_template( $backup_dir ) {
	$existing = get_posts(
		[
			'post_type'      => 'elementor_library',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_elementor_template_type',
			'meta_value'     => 'footer',
		]
	);

	$footer_id = $existing ? (int) $existing[0]->ID : 0;

	if ( $footer_id ) {
		cc_backup_payload(
			$backup_dir,
			'footer-template-before.json',
			[
				'ID'                    => $footer_id,
				'_elementor_data'       => json_decode( get_post_meta( $footer_id, '_elementor_data', true ), true ),
				'_elementor_conditions' => get_post_meta( $footer_id, '_elementor_conditions', true ),
			]
		);
	} else {
		$footer_id = wp_insert_post(
			[
				'post_type'   => 'elementor_library',
				'post_title'  => 'Elementor Footer',
				'post_status' => 'publish',
			]
		);
	}

	if ( ! $footer_id || is_wp_error( $footer_id ) ) {
		return 0;
	}

	update_post_meta( $footer_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $footer_id, '_elementor_template_type', 'footer' );
	update_post_meta( $footer_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.35.9' );
	update_post_meta( $footer_id, '_elementor_pro_version', defined( 'ELEMENTOR_PRO_VERSION' ) ? ELEMENTOR_PRO_VERSION : '' );
	update_post_meta( $footer_id, '_wp_page_template', 'default' );
	update_post_meta( $footer_id, '_elementor_conditions', 'a:1:{i:0;s:15:"include/general";}' );
	update_post_meta( $footer_id, '_elementor_data', wp_slash( wp_json_encode( cc_footer_template_payload(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) );
	clean_post_cache( $footer_id );

	return (int) $footer_id;
}

$kit_id       = 586;
$logo_id      = 38605;
$favicon_id   = 38614;
$kit_settings = get_post_meta( $kit_id, '_elementor_page_settings', true );

cc_backup_payload( $backup_dir, 'kit-586-page-settings-before.json', $kit_settings );

$kit_settings['site_name']                 = '卓越獎品 Premier Trophy';
$kit_settings['site_logo']                 = [
	'url'         => wp_get_attachment_url( $logo_id ),
	'id'          => $logo_id,
	'size'        => 'full',
	'isLoading'   => false,
	'aspectRatio' => '1:1',
	'alt'         => 'Premier Trophy',
	'source'      => 'library',
];
$kit_settings['site_favicon']              = [
	'url'    => wp_get_attachment_url( $favicon_id ),
	'id'     => $favicon_id,
	'size'   => 'full',
	'alt'    => 'Premier Trophy',
	'source' => 'library',
];
$kit_settings['woocommerce_shop_page_id'] = '40992';

$custom_css = isset( $kit_settings['custom_css'] ) && is_string( $kit_settings['custom_css'] )
	? $kit_settings['custom_css']
	: '';

$custom_css = preg_replace(
	'#/\* Hide cart UI — catalogue mode \*/.*?(?=/\* Hide prices globally \*/)#s',
	'',
	$custom_css
);

$safety_css = "\n/* Quote basket: keep cart visible */\nheader .elementor-widget-woocommerce-menu-cart,\nheader .elementor-menu-cart__wrapper,\nheader .elementor-menu-cart__container{display:block!important}\n.wpml-ls-statics-footer,.otgs-development-site-front-end{display:none!important}\n";
if ( strpos( $custom_css, 'Quote basket: keep cart visible' ) === false ) {
	$custom_css .= $safety_css;
}
$kit_settings['custom_css'] = $custom_css;

update_post_meta( $kit_id, '_elementor_page_settings', $kit_settings );
set_theme_mod( 'custom_logo', $logo_id );
update_option( 'site_icon', $favicon_id );
update_post_meta( $logo_id, '_wp_attachment_image_alt', 'Premier Trophy' );
update_post_meta( $favicon_id, '_wp_attachment_image_alt', 'Premier Trophy' );
cc_sync_attachment_alts();

$page_backups = [];
foreach ( [ 38090 => 'zh-hant', 41143 => 'en' ] as $page_id => $lang ) {
	$previous = cc_patch_homepage_data( $page_id, $lang );
	if ( $previous ) {
		$page_backups[ $page_id ] = json_decode( $previous, true );
	}
}

if ( $page_backups ) {
	cc_backup_payload( $backup_dir, 'homepage-elementor-before.json', $page_backups );
}

$brand_pages = [
	38679 => [ 'shortcode' => '[cc_about_page]', 'prefix' => 'ccaboutzh' ],
	41101 => [ 'shortcode' => '[cc_about_page]', 'prefix' => 'ccabouten' ],
	38221 => [ 'shortcode' => '[cc_cases_page]', 'prefix' => 'cccasezh' ],
	41134 => [ 'shortcode' => '[cc_cases_page]', 'prefix' => 'cccaseen' ],
	38310 => [ 'shortcode' => '[cc_art_page]', 'prefix' => 'ccartzh' ],
	41110 => [ 'shortcode' => '[cc_art_page]', 'prefix' => 'ccarten' ],
];

$brand_page_backups = [];
foreach ( $brand_pages as $page_id => $spec ) {
	$brand_page_backups[ $page_id ] = json_decode(
		cc_patch_shortcode_page( $page_id, $spec['shortcode'], $spec['prefix'] ),
		true
	);
}

cc_backup_payload( $backup_dir, 'brand-pages-before.json', $brand_page_backups );

$product_backups = [];
$products        = get_posts(
	[
		'post_type'        => 'product',
		'post_status'      => [ 'publish', 'private' ],
		'numberposts'      => -1,
		'suppress_filters' => false,
	]
);

foreach ( $products as $product ) {
	$updated_post = [
		'ID' => $product->ID,
	];
	$dirty = false;

	if ( preg_match( '/(?:u[0-9a-fA-F]{4}){2,}/', $product->post_content ) ) {
		$decoded_content = cc_decode_u_sequences_for_storefront( $product->post_content );
		if ( $decoded_content !== $product->post_content ) {
			$updated_post['post_content'] = $decoded_content;
			$dirty                        = true;
		}
	}

	if ( preg_match( '/(?:u[0-9a-fA-F]{4}){2,}/', $product->post_excerpt ) ) {
		$decoded_excerpt = cc_decode_u_sequences_for_storefront( $product->post_excerpt );
		if ( $decoded_excerpt !== $product->post_excerpt ) {
			$updated_post['post_excerpt'] = $decoded_excerpt;
			$dirty                        = true;
		}
	}

	if ( ! $dirty ) {
		continue;
	}

	$product_backups[ $product->ID ] = [
		'title'        => $product->post_title,
		'post_content' => $product->post_content,
		'post_excerpt' => $product->post_excerpt,
	];

	wp_update_post( $updated_post );
}

if ( $product_backups ) {
	cc_backup_payload( $backup_dir, 'product-content-before.json', $product_backups );
}

$normalized_en_products = cc_normalize_en_products( $backup_dir );
$normalized_en_categories = cc_normalize_en_category_terms( $backup_dir );
$updated_category_thumbnails = cc_sync_category_thumbnails( $backup_dir );
$patched_catalog_media       = cc_patch_broken_catalog_media( $backup_dir );
$footer_template_id          = cc_upsert_footer_template( $backup_dir );

if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

flush_rewrite_rules( true );
do_action( 'litespeed_purge_all' );

echo 'backup_dir=' . $backup_dir . PHP_EOL;
echo 'homepage_pages=' . count( $page_backups ) . PHP_EOL;
echo 'brand_pages=' . count( $brand_page_backups ) . PHP_EOL;
echo 'decoded_products=' . count( $product_backups ) . PHP_EOL;
echo 'normalized_en_products=' . $normalized_en_products . PHP_EOL;
echo 'normalized_en_categories=' . $normalized_en_categories . PHP_EOL;
echo 'updated_category_thumbnails=' . $updated_category_thumbnails . PHP_EOL;
echo 'patched_catalog_media=' . $patched_catalog_media . PHP_EOL;
echo 'footer_template_id=' . $footer_template_id . PHP_EOL;
