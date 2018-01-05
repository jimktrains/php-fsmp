<?php
require_once("Stateful.php");
require_once("Process.php");
require_once("FilterReduceAccum.php");
require_once("FilterReduce.php");
require_once("example_models.php");


class ShippingCostAccum implements FilterReduceAccum
{
  public $giftCards = 0;
  public $flat      = 0;

  public function addGiftcard($item)
  {
    $this->giftCards += 1.5;
  }

  public function addFlat($item)
  {
    $this->flat = 5;
  }

  public function getFinal()
  {
    return max($this->giftCards, $this->flat);
  }

  public function getCheck()
  {
    return true;
  }
}

// Each invocation, use a new ShippingCostAccum.
$ShippingCostFilterReduce = new FilterReduce(ShippingCostAccum::class);

// Reduce over all the items in the order.
$ShippingCostFilterReduce->over(function ($Order) {
  return $Order->items;
});

// If it's a giftcard, add the giftcard shipping cost.
// 'isGiftcard' is a method called on the model being processed.
// In this case, `over` (above) gives us an Item
$ShippingCostFilterReduce->add('isGiftcard', 'addGiftcard');

// null=everything else, add the flat rate
$ShippingCostFilterReduce->add(null, 'addFlat');

class QualifedFreeShippingAccum implements FilterReduceAccum
{
  private $cost = 0;
  public function addItem($item)
  {
    $this->cost += $item->price;
  }

  public function getFinal()
  {
    return 0;
  }

  public function getCheck()
  {
    return $this->cost > 100;
  }
}

// Each invocation use a new QualifedFreeShippingAccum
$QualifedFreeShippingFilterReduce = new FilterReduce(QualifedFreeShippingAccum::class);

// Reduce over all the items in an order.
$QualifedFreeShippingFilterReduce->over(function ($Order) {
  return $Order->items;
});

// If it's a giftcard, don't do anything.
$QualifedFreeShippingFilterReduce->add('isGiftcard', null);

// null=everything else, addItem.
$QualifedFreeShippingFilterReduce->add(null, 'addItem');

// Create a new process to compute shipping. Starts in the 'initial' state.
$ComputeShipping = new Process();

// First see if the order qualifies for free shipping.
// name=free_shipping
// begining state=initial
// transition to=shipping_computed
// only transition if `getCheck` === true for QualifedFreeShippingFilterReduce
$ComputeShipping->transition('free_shipping', 'initial', 'shipping_computed', $QualifedFreeShippingFilterReduce);

// If it doesn't, compute hte shipping price.
$ComputeShipping->transition('flat_shipping', 'initial', 'shipping_computed', $ShippingCostFilterReduce);

// Once we've computed the shipping, set the shipping total to the accumulator.
$ComputeShipping->state('shipping_computed', function ($Order, $Accum) {
  $Order->shipping_subtotal = $Accum->getFinal();
});



///////////////////////
// Examples
//////////////////////


echo("=======================" . "\r\n");
echo("No Items" . "\r\n");
echo("=======================" . "\r\n");
$Order = new Order;
$ComputeShipping($Order);
echo("Final Shipping Subtotal: " . json_encode($Order->shipping_subtotal) . "\r\n");

// =======================
// No Items
// =======================
// Order initial => shipping_computed (free_shipping: false)
// Order initial => shipping_computed (flat_shipping: true)
// Final Shipping Subtotal: 0





echo("" . "\r\n");
echo("=======================" . "\r\n");
echo("One Giftcard           " . "\r\n");
echo("=======================" . "\r\n");

$Order = new Order;
$Order->items = [
  new Giftcard,
];
$ComputeShipping($Order);
echo("Final Shipping Subtotal: " . json_encode($Order->shipping_subtotal) . "\r\n");

// =======================
// One Giftcard
// =======================
//         Filtering Giftcard
//                 Filter isGiftcard: true
// Order initial => shipping_computed (free_shipping: false)
//         Filtering Giftcard
//                 Filter isGiftcard: true
// Order initial => shipping_computed (flat_shipping: true)
// Final Shipping Subtotal: 1.5





echo("" . "\r\n");
echo("=======================" . "\r\n");
echo("Two Giftcard           " . "\r\n");
echo("=======================" . "\r\n");

$Order = new Order;
$Order->items = [
  new Giftcard,
  new Giftcard,
];
$ComputeShipping($Order);
echo("Final Shipping Subtotal: " . json_encode($Order->shipping_subtotal) . "\r\n");

// =======================
// Two Giftcard
// =======================
//         Filtering Giftcard
//                 Filter isGiftcard: true
//         Filtering Giftcard
//                 Filter isGiftcard: true
// Order initial => shipping_computed (free_shipping: false)
//         Filtering Giftcard
//                 Filter isGiftcard: true
//         Filtering Giftcard
//                 Filter isGiftcard: true
// Order initial => shipping_computed (flat_shipping: true)
// Final Shipping Subtotal: 3





echo("" . "\r\n");
echo("=======================" . "\r\n");
echo("One Giftcard One Shirt " . "\r\n");
echo("=======================" . "\r\n");

$Order = new Order;
$Order->items = [
  new Giftcard,
  new Shirt,
];
$ComputeShipping($Order);
echo("Final Shipping Subtotal: " . json_encode($Order->shipping_subtotal) . "\r\n");

echo("" . "\r\n");
echo("=======================" . "\r\n");
echo("One Giftcard Two Shirt " . "\r\n");
echo("=======================" . "\r\n");

// =======================
// One Giftcard One Shirt
// =======================
//         Filtering Giftcard
//                 Filter isGiftcard: true
//         Filtering Shirt
//                 Filter isGiftcard: false
//                 Filter: default
// Order initial => shipping_computed (free_shipping: false)
//         Filtering Giftcard
//                 Filter isGiftcard: true
//         Filtering Shirt
//                 Filter isGiftcard: false
//                 Filter: default
// Order initial => shipping_computed (flat_shipping: true)
// Final Shipping Subtotal: 5





$Order = new Order;
$Order->items = [
  new Giftcard,
  new Shirt,
  new Shirt,
];
$ComputeShipping($Order);
echo("Final Shipping Subtotal: " . json_encode($Order->shipping_subtotal) . "\r\n");

// =======================
// One Giftcard Two Shirt
// =======================
//         Filtering Giftcard
//                 Filter isGiftcard: true
//         Filtering Shirt
//                 Filter isGiftcard: false
//                 Filter: default
//         Filtering Shirt
//                 Filter isGiftcard: false
//                 Filter: default
// Order initial => shipping_computed (free_shipping: true)
// Final Shipping Subtotal: 0
