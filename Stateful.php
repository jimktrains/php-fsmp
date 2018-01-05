<?php

interface Stateful
{
  public function currentState();
  public function setState($stateName);
}

