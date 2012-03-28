<?php

class Item extends Eloquent
{
	public static $table = 'items';

	public function items()
	{
		return $this->has_many('ListItem');
	}
}