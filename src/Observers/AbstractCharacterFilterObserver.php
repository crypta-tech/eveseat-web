<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015 to present Leon Jacobs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Seat\Web\Observers;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Web\Events\AuthedCharacterFilterDataUpdate;
use Seat\Web\Exceptions\InvalidFilterException;
use Seat\Web\Models\User;

/**
 * Class AbstractSquadObserver.
 *
 * @package Seat\Web\Observers
 *
 * @deprecated This class will be removed in SeAT 6
 */
abstract class AbstractCharacterFilterObserver
{
    /**
     * Return the User owning the model which fired the catch event.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $fired_model  The model which fired the catch event
     * @return ?CharacterInfo The character that is affected by this update
     */
    abstract protected function findRelatedCharacter(Model $fired_model): ?CharacterInfo;

    /**
     * Update squads to which the user owning model firing the event is member.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $fired_model  The model which fired the catch event
     *
     * @throws InvalidFilterException
     */
    protected function fireCharacterFilterEvent(Model $fired_model): void
    {
        $character = $this->findRelatedCharacter($fired_model);

        if (! $character)
            return;

        $token = $character->refresh_token;
        if($token === null)
            return;

        event(new AuthedCharacterFilterDataUpdate($token));
    }
}
