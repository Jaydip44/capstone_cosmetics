<?php

namespace Drupal\commerce_order\Event;

final class OrderEvents {

  /**
   * Name of the event fired when assigning an order to a customer.
   *
   * Fired when assigning an anonymous order to a customer (e.g. after the
   * customer logged in / registered at the end of checkout), and when
   * reassigning an order to a different customer in the admin UI.
   *
   * Note:
   * At this point the order still has the original data (customer, email).
   * Use $event->getOrder()->getCustomer()->isAnonymous() to check whether the
   * original customer was anonymous.
   *
   * Fired before the order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderAssignEvent
   */
  const ORDER_ASSIGN = 'commerce_order.order.assign';

  /**
   * Name of the event fired when altering an order label.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderLabelEvent
   */
  const ORDER_LABEL = 'commerce_order.order.label';

  /**
   * Name of the event fired after the order has been fully paid.
   *
   * Guaranteed to only fire once, when the order balance reaches zero.
   * Subsequent changes to the balance won't re-dispatch the event (e.g. in case
   * of a refund followed by an additional payment).
   *
   * Fired before the order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\OrderInterface::getBalance()
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_PAID = 'commerce_order.order.paid';

  /**
   * Name of the event fired when collecting order profiles.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderProfilesEvent
   */
  const ORDER_PROFILES = 'commerce_order.profiles';

  /**
   * Name of the event fired after loading an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_LOAD = 'commerce_order.commerce_order.load';

  /**
   * Name of the event fired after creating a new order.
   *
   * Fired before the order is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_CREATE = 'commerce_order.commerce_order.create';

  /**
   * Name of the event fired before saving an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_PRESAVE = 'commerce_order.commerce_order.presave';

  /**
   * Name of the event fired after saving a new order.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_INSERT = 'commerce_order.commerce_order.insert';

  /**
   * Name of the event fired after saving an existing order.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_UPDATE = 'commerce_order.commerce_order.update';

  /**
   * Name of the event fired before deleting an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_PREDELETE = 'commerce_order.commerce_order.predelete';

  /**
   * Name of the event fired after deleting an order.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderEvent
   */
  const ORDER_DELETE = 'commerce_order.commerce_order.delete';

  /**
   * Name of the event fired after loading an order item.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderItemEvent
   */
  const ORDER_ITEM_LOAD = 'commerce_order.commerce_order_item.load';

  /**
   * Name of the event fired after creating a new order item.
   *
   * Fired before the order item is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderItemEvent
   */
  const ORDER_ITEM_CREATE = 'commerce_order.commerce_order_item.create';

  /**
   * Name of the event fired before saving an order item.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderItemEvent
   */
  const ORDER_ITEM_PRESAVE = 'commerce_order.commerce_order_item.presave';

  /**
   * Name of the event fired after saving a new order item.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderItemEvent
   */
  const ORDER_ITEM_INSERT = 'commerce_order.commerce_order_item.insert';

  /**
   * Name of the event fired after saving an existing order item.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderItemEvent
   */
  const ORDER_ITEM_UPDATE = 'commerce_order.commerce_order_item.update';

  /**
   * Name of the event fired before deleting an order item.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderItemEvent
   */
  const ORDER_ITEM_PREDELETE = 'commerce_order.commerce_order_item.predelete';

  /**
   * Name of the event fired after deleting an order item.
   *
   * @Event
   *
   * @see \Drupal\commerce_order\Event\OrderItemEvent
   */
  const ORDER_ITEM_DELETE = 'commerce_order.commerce_order_item.delete';

}
