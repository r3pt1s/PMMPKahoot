<?php

namespace r3pt1s\kahoot\game;

class GameSettings {

    public function __construct(
        private readonly bool $allowCustomNames,
        private readonly bool $hostCanPlay
    ) {}

    public function isAllowCustomNames(): bool {
        return $this->allowCustomNames;
    }

    public function isHostCanPlay(): bool {
        return $this->hostCanPlay;
    }
}