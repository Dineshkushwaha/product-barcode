<?php

namespace Drupal\product_barcode\Plugin\Block;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a block with QR code.
 *
 * @Block(
 *   id = "product_barcode_block",
 *   admin_label = @Translation("Product Barcode Block"),
 * )
 */
class BarcodeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * RouteMatch used to get parameter Node.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a Drupal\product_barcode\Plugin\block\BarcodeBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // Set cache contexts.
    $cachableMetadata = new CacheableMetadata();
    $cachableMetadata->setCacheContexts(['route']);

    // Show QR code only on Product pages.
    if (($node = $this->routeMatch->getParameter('node'))
      && $node instanceof NodeInterface && $node->bundle() === 'product'
      && class_exists('TCPDF2DBarcode')) {
      // Add cacheable dependency.
      $cachableMetadata->addCacheableDependency($node);
      $purchase_link = $node->field_purchase_link->uri;

      // Create barcode obj.
      $barcode_obj = new \TCPDF2DBarcode($purchase_link, 'QRCODE,H');
      $build = [
        '#type' => 'inline_template',
        '#template' => $barcode_obj->getBarcodeHTML(5, 5, 'black'),
      ];
    }
    $cachableMetadata->applyTo($build);
    return $build;
  }

}
