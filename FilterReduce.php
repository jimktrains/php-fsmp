<?php

class FilterReduce
{
  private $accumClass;
  private $overCallback;
  private $filters;

  public function __construct($accumClass)
  {
    $this->accumClass = $accumClass;
		$this->overCallback = function ($x) { return [$x]; };
  }

  public function over($over)
  {
    $this->overCallback = $over;
  }

  public function add($filter, $map)
  {
    $this->filters[] = [
                    'filter' => $filter,
                    'map'    => $map,
                  ];
  }

  public function __invoke($x)
  {
    $accum = new $this->accumClass;

    foreach (($this->overCallback)($x) as $i) {
      echo("\tFiltering " . get_class($i) . "\r\n");
      foreach ($this->filters as $filter) {
        $filterCheck = $filter['filter'];
        $is_default = empty($filterCheck);
        $check      = false;
        if ($is_default) {
          echo("\t\tFilter: default" . "\r\n");
        } else {
          $check = $i->{$filterCheck}($i);
          echo("\t\tFilter $filterCheck: " . json_encode($check) . "\r\n");
        }
        if ($is_default || $check) {
          $callback = $filter['map'];
          if (!empty($callback)) {
            $accum->{$callback}($i);
          }
          break;
        }
      }
    }

    return $accum;
  }
}
