<?php

namespace Drupal\Core\Security;

/**
 * Interface to declare trusted callbacks.
 *
 * @see \Drupal\Core\Security\DoTrustedCallbackTrait
 */
interface TrustedCallbackInterface {

  /**
   * Untrusted callbacks throw exceptions.
   */
  const THROW_EXCEPTION = 'exception';

  /**
   * Untrusted callbacks trigger E_USER_WARNING errors.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
   *   TrustedCallbackInterface::THROW_EXCEPTION or
   *   TrustedCallbackInterface::TRIGGER_SILENCED_DEPRECATION instead.
   *
   * @see https://www.drupal.org/node/3427367
   */
  const TRIGGER_WARNING = 'warning';

  /**
   * Untrusted callbacks trigger silenced E_USER_DEPRECATION errors.
   */
  const TRIGGER_SILENCED_DEPRECATION = 'silenced_deprecation';

  /**
   * Lists the trusted callbacks provided by the implementing class.
   *
   * Trusted callbacks are public methods on the implementing class and can be
   * invoked via
   * \Drupal\Core\Security\DoTrustedCallbackTrait::doTrustedCallback().
   *
   * @return string[]
   *   List of method names implemented by the class that can be used as trusted
   *   callbacks.
   *
   * @see \Drupal\Core\Security\DoTrustedCallbackTrait::doTrustedCallback()
   */
  public static function trustedCallbacks();

}
