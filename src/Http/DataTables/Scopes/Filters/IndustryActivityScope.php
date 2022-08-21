<?php

/*
 * This file is part of SeAT
 *
 * Copyright (C) 2015 to 2022 Leon Jacobs
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

namespace Seat\Web\Http\DataTables\Scopes\Filters;

use Yajra\DataTables\Contracts\DataTableScope;

/**
 * Class IndustryActivityScope.
 *
 * This is a filter scope for industry tables.
 * It will restrict returned data based on job activity.
 *
 * @package Seat\Web\Http\DataTables\Scopes\Filters
 */
class IndustryActivityScope implements DataTableScope
{
    /**
     * @var array
     */
    private $activities = [];

    /**
     * IndustryScope constructor.
     *
     * @param  array  $activities
     */
    public function __construct(?array $activities)
    {
        $this->activities = $activities ?: [];
    }

    /**
     * Apply a query scope.
     *
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder  $query
     * @return mixed
     */
    public function apply($query)
    {
        return $query->whereIn('activity_id', $this->activities);
    }
}
