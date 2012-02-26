<?php

class Item extends Eloquent\Model 
{
	public static $table = 'items';

	public function items()
	{
		return $this->has_many('ListItem');
	}
}