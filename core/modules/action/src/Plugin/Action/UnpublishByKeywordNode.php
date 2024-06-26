<?php

declare(strict_types=1);

namespace Drupal\action\Plugin\Action;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Unpublishes a node containing certain keywords.
 */
#[Action(
  id: 'node_unpublish_by_keyword_action',
  label: new TranslatableMarkup('Unpublish content containing keyword(s)'),
  type: 'node'
)]
class UnpublishByKeywordNode extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    $elements = \Drupal::entityTypeManager()
      ->getViewBuilder('node')
      ->view(clone $node);
    $render = (string) \Drupal::service('renderer')->renderInIsolation($elements);
    foreach ($this->configuration['keywords'] as $keyword) {
      if (str_contains($render, $keyword) || str_contains($node->label(), $keyword)) {
        $node->setUnpublished();
        $node->save();
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'keywords' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['keywords'] = [
      '#title' => $this->t('Keywords'),
      '#type' => 'textarea',
      '#description' => $this->t('The content will be unpublished if it contains any of the phrases above. Use a case-sensitive, comma-separated list of phrases. Example: funny, bungee jumping, "Company, Inc."'),
      '#default_value' => Tags::implode($this->configuration['keywords']),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['keywords'] = Tags::explode($form_state->getValue('keywords'));
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
