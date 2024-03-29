<?php

    class ControllerToolFoks extends Controller {

        public function index() {

            $date   = date( 'Y-m-d H:i:s' );
            $output = '';
            $output .= '<?xml version="1.0" encoding="utf-8"?>' . "\n";
            $output .= '<!DOCTYPE yml_catalog SYSTEM "shops.dtd">' . "\n";
            $output .= '<yml_catalog date="' . $date . '">' . "\n";
            $output .= '<shop>' . "\n";

            $this->load->model( 'catalog/product' );
            $this->load->model( 'tool/image' );

            $products = $this->model_catalog_product->getProducts();

            $protocol    = strpos( strtolower( $_SERVER['SERVER_PROTOCOL'] ), 'https' ) === FALSE ? 'http' : 'https';
            $domainLink  = $protocol . '://' . $_SERVER['HTTP_HOST'];
            $config_name = $this->config->get( 'config_name' );

            $name    = $config_name;
            $company = $config_name;
            $url     = $domainLink;

            $this->load->model( 'catalog/category' );
            $data['categories'] = array();
            $categories         = $this->model_catalog_category->getCategories( 0 );

            foreach ( $categories as $category ) {
                $children_data = array();
                $children      = $this->model_catalog_category->getCategories( $category['category_id'] );

                foreach ( $children as $child ) {
                    $filter_data     = array( 'filter_category_id' => $child['category_id'], 'filter_sub_category' => true );
                    $children_data[] = array(
                        'category_id' => $child['category_id'],
                        'name'        => $child['name'] . ($this->config->get( 'config_product_count' ) ? ' (' . $this->model_catalog_product->getTotalProducts( $filter_data ) . ')' : ''),
                    );
                }

                $filter_data = array(
                    'filter_category_id'  => $category['category_id'],
                    'filter_sub_category' => true
                );

                $data['categories'][] = array(
                    'category_id' => $category['category_id'],
                    'name'        => $category['name'] . ($this->config->get( 'config_product_count' ) ? ' (' . $this->model_catalog_product->getTotalProducts( $filter_data ) . ')' : ''),
                    'children'    => $children_data,
                );
            }

            $output .= '<name>' . $name . '</name>' . "\n";
            $output .= '<company>' . $company . '</company>' . "\n";
            $output .= '<url>' . $url . '</url>' . "\n";
            $output .= '<currencies>' . "\n";
            $output .= '<currency id="UAH" rate="1" plus="0" />' . "\n";
            $output .= '</currencies>' . "\n";
            $output .= '<categories>' . "\n";

            foreach ( $data['categories'] as $item ) {
                $output .= "\t" . '<category id="' . $item['category_id'] . '">' . $item['name'] . '</category>' . "\n";

                if ( !empty( $item['children'] ) ) {

                    foreach ( $item['children'] as $child ) {
                        $output .= "\t" . '<category parent_id="' . $item['category_id'] . '" id="' . $child['category_id'] . '">' . $child['name'] . '</category>' . "\n";
                    }
                }

            }
            $output .= '</categories>' . "\n";

            $output .= '<offers>' . "\n";

            foreach ( $products as $product ) {

                if ( $product['status'] ) {

                    $product['images'] = array();

                    $images = $this->model_catalog_product->getProductImages( $product['product_id'] );

                    if ( $images ) {

                        foreach ( $images as $img ) {
                            $product['images'][] = [
                                'popup' => $domainLink . '/image/' . $img['image'],
                            ];
                        }

                    }

                    $price_format = sprintf( '%01.2f', $product['price'] );
                    $price        = $price_format ? $price_format : 1;
                    $attributes   = $this->model_catalog_product->getProductAttributes( $product['product_id'] );
                    $cat = $this->clearCat( $this->model_catalog_product->getCategories( $product['product_id'] ) );
                    $thumb = $domainLink . '/image/' . $product['image'];

                    if ( !$thumb ) {
                        $thumb = '';
                    }

                    $output .= '<offer id="' . $product['product_id'] . '" available="true">' . "\n";
                    $output .= '<name>' . htmlspecialchars($product['name']) . '</name>' . "\n";
                    $output .= '<price>' . $price . '</price>' . "\n";
                    $output .= '<categoryId>' . $cat . '</categoryId>' . "\n";
                    $output .= '<picture>' . $thumb . '</picture>' . "\n";

                    foreach ( $product['images'] as $pr_img ) {
                        $output .= '<picture>' . $pr_img['popup'] . '</picture>' . "\n";
                    }

                    $output .= '<currencyId>UAH</currencyId>' . "\n";
                    $output .= '<stock_quantity>' . $product['quantity'] . '</stock_quantity>' . "\n";

                    if ( $product['manufacturer'] ) {
                        $output .= '<vendor>' . htmlspecialchars($product['manufacturer']) . '</vendor>' . "\n";
                    }

                    $output .= '<description>' . htmlspecialchars( $product['description'] ) . "\n";
                    $output .= '</description>' . "\n";

                    if ( count( $attributes ) ) {

                        foreach ( $attributes as $attr ) {
                            if ( $attr['attribute'] && count( $attr ) ) {
                                foreach ($attr['attribute'] as  $item) {
                                    $output  .= "<param name=\"".htmlspecialchars($item['name'])."\">".htmlspecialchars($item['text'])."</param>";
                                }
                            }
                        }

                    }

                    $output .= '</offer>' . "\n";
                }
            }

            $output .= '</offers>' . "\n";
            $output .= '</shop>' . "\n";
            $output .= '</yml_catalog>';

            $this->response->addHeader( 'Content-Type: application/xml' );
            $this->response->setOutput( $output );

        }

        public function clearCat( $cats ) {
            $res = '';

            foreach ( $cats as $cat ) {
                $res .= $cat['category_id'];
            }

            return $res;
        }

    }
