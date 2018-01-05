<?php

class Process
{
  private $transitions = [];
  private $states      = [];

  public function state($name, $callback)
  {
    $this->states[$name] = $callback;
  }

  public function transition($name, $from, $to, $callback)
  {
    if (!array_key_exists($from, $this->transitions)) {
      $this->transitions[$from] = [];
    }
    if (!array_key_exists($to, $this->transitions[$from])) {
      $this->transitions[$from][$to] = [];
    }
    $this->transitions[$from][$to][] = [
                                    'name'     => $name,
                                    'callback' => $callback,
                                  ];
  }

  public function next(Stateful $stateful)
  {
    $statefulClass = get_class($stateful);
    $from  = $stateful->currentState();
    foreach ($this->transitions[$from] as $to => $transitions) {
      foreach ($transitions as $transition) {
        $is_default = empty($transition['name']);
        $accum = $transition['callback']($stateful);
        if ($is_default) {
          echo("$statefulClass $from => $to (default)" . "\r\n");
        } else {
          echo("$statefulClass $from => $to ({$transition['name']}: ".json_encode($accum->getCheck()).")" . "\r\n");
        }
        if ($is_default || $accum->getCheck()) {
          $stateful->setState($to);
          $callback = $this->states[$to];
          $callback($stateful, $accum);
          // return $this->next($stateful);
          return;
        }
      }
    }

    return null;
  }

  public function __invoke($stateful)
  {
    return $this->next($stateful);
  }
}
