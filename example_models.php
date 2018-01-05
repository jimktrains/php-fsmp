<?php

class Order implements Stateful
{
  private $state;
  public function currentState()
  {
    if (empty($state)) {
      return 'initial';
    }
    return $this->state;
  }

  public function setState($stateName)
  {
    $this->state = $stateName;
  }

  public $items = [];

  public $shipping_subtotal = 0;
}

class Product implements Stateful
{
  public $is_giftcard = false;
  public $price       = 0;
  private $state;

  public function isGiftcard() {
    return $this->is_giftcard;
  }

  public function currentState()
  {
    if (empty($state)) {
      return 'initial';
    }
    return $this->state;
  }

  public function setState($stateName)
  {
    $this->state = $stateName;
  }
}

class Giftcard extends Product
{
  public $is_giftcard = true;
  public $price = 53;
}

class Shirt extends Product
{
  public $is_giftcard = false;
  public $price = 55;
}

