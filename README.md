PHP Finite State Machine Processes
----------------------------------

The goal is to design processes that are easy to construct, understand,
and modify without too much of a hassle or fear about dependencies.

The basic idea is that you start with a `Process` that is similar to a
Finite State Machine starting in an 'initial' state. You provide the
`Process` with a model to work with.  Conditional transitions between
states are then added, and executed in the order of addition.
Conditional because the transition is only taken if the `Reducer` is
satisfied.

The `Reducer` creates a new `Accum`ulator, and then extracts a list from
the model provided to the process using `over` and processes them. For a
`FilterReduce`, filters are applied, in order, to each element in the
list: Some method on the element and if it's true, then the
corrosponding method on the `Accum` is called.

An `Accum` looks like this:


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

A `FilterReduce` looks like this:

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

A `Process` looks like this:

    $ComputeShipping = new Process();
    
    // First see if the order qualifies for free shipping.
    // name=free_shipping
    // begining state=initial
    // transition to=shipping_computed
    // only transition if `getCheck` === true for QualifedFreeShippingFilterReduce
    $ComputeShipping->transition('free_shipping', 'initial', 'shipping_computed', $QualifedFreeShippingFilterReduce);
    
    // Once we've computed the shipping, set the shipping total to the accumulator.
    $ComputeShipping->state('shipping_computed', function ($Order, $Accum) {
      $Order->shipping_subtotal = $Accum->getFinal();
    });

Example Usage:

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

See [test.php](test.php) for a slightly more complete example.
