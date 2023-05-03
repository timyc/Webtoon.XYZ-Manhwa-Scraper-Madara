<?php

    if( class_exists('WT_CRAWLER_ACTIONS') ){

        class WT_CRAWLER_HELPERS extends WT_CRAWLER_ACTIONS{

            public static function is_settings_page(){

                if( strpos( $_SERVER['REQUEST_URI'], 'admin.php?page=webtoon-manga-crawler-settings' ) !== false ){
                    return true;
                }

            }

            public static function is_crawler_active(){

                $settings = WT_CRAWLER_HELPERS::get_crawler_settings();

                return !empty( $settings['active'] );

            }

            public static function get_template( $name, $extend_name = null ){

                if( $extend_name ){
                    $name .= $extend_name;
                }

                $path = WP_MCL_WT_PATH . "template/$name.php";

                if( file_exists( $path ) ){
                    include( $path );
                }

                return;

            }

            public static function get_crawler_settings(){

                $settings = get_option( '_wt_crawler_settings' );

                $defaults = array(
                    'fetch'               => array(
                        'genres'          => '0',
                        'tags'            => '0',
                        'ratings'         => '0',
                        'views'           => '0',
                    ),
                    'import'              => array(
                        'status'          => 'pending',
                        'genres'          => array(),
                        'tags'            => '',
						'storage'		  => 'local'
                    ),
                    'cronjob'             => array(
                        'recurrence'      => 3,
                        'number_chapters' => 1
                    ),
                    'active'              => 0,
					'update' 			  => 0
                );

                return !empty( $settings ) ? array_merge( $defaults, $settings ) : $defaults;
            }

            public static function update_crawler_settings( $settings ){

                $options = get_option( '_wt_crawler_settings', array() );

                return update_option( '_wt_crawler_settings', array_merge( $options, $settings ) );

            }

            public static function update_crawler_proxy_stt( $stt ){
                $settings = self::get_crawler_settings();

                $settings['proxy']['status'] = $stt;

                return self::update_crawler_settings( $settings );
            }
        }


    }
