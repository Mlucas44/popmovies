<?php

namespace Drupal\tmdb_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tmdb_api\Service\TmdbClient;

/**
 * Form for the region/country selector.
 */
class RegionSelectorForm extends FormBase
{

  /**
   * Le service TMDB Client.
   *
   * @var \Drupal\tmdb_api\Service\TmdbClient
   */
  protected $tmdbClient;

  /**
   * Constructs a new RegionSelectorForm object.
   *
   * @param \Drupal\tmdb_api\Service\TmdbClient $tmdb_client
   *   The TMDB client service.
   */
  public function __construct(TmdbClient $tmdb_client)
  {
    $this->tmdbClient = $tmdb_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('tmdb_api.client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'tmdb_api_region_selector_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['#cache']['contexts'][] = 'tmdb_region';

    $regions = $this->tmdbClient->getAvailableRegions();

    $options = [];
    foreach ($regions as $region) {
      $iso = $region['iso_3166_1'] ?? '';
      $name = $region['native_name'] ?? $region['english_name'] ?? $iso;
      if ($iso) {
        $options[$iso] = $name;
      }
    }

    asort($options);

    $session = $this->getRequest()->getSession();
    $default_region = $session->get('tmdb_api_region', 'FR');

    $form['region'] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default_region,
      '#attributes' => [
        'class' => ['bg-transparent', 'text-pop-blanc/80', 'border', 'border-pop-toile', 'rounded-md', 'px-2', 'py-1.5', 'text-sm', 'focus:outline-none', 'focus:border-pop-or', 'hover:text-pop-blanc', 'cursor-pointer', 'transition-colors'],
        'onchange' => 'this.closest("form").querySelector("input[type=submit]").click();',
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Changer'),
      '#attributes' => ['class' => ['hidden']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $region = $form_state->getValue('region');

    // Session Management for Anonymous Users
    $session_manager = \Drupal::service('session_manager');
    if (\Drupal::currentUser()->isAnonymous()) {
      if (!$session_manager->isStarted()) {
        $session_manager->start();
      }
      $_SESSION['tmdb_api_anonymous_force'] = TRUE;
    }

    // Direct storage of the value in the Drupal/Symfony session
    $this->getRequest()->getSession()->set('tmdb_api_region', $region);

    // PRG Pattern: Post -> Redirect -> Get
    $url = \Drupal\Core\Url::fromRoute('<current>');
    $form_state->setRedirectUrl($url);
  }
}
