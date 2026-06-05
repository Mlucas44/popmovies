<?php

namespace Drupal\tmdb_api\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "TMDB Region Selector" block.
 *
 * @Block(
 *   id = "tmdb_api_region_selector",
 *   admin_label = @Translation("TMDB: Sélecteur de région"),
 *   category = @Translation("TMDB")
 * )
 */
class RegionSelectorBlock extends BlockBase implements ContainerFactoryPluginInterface
{

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a RegionSelectorBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    return $this->formBuilder->getForm('\Drupal\tmdb_api\Form\RegionSelectorForm');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts()
  {
    return ['session'];
  }
}
