<?php

class Page extends Eloquent
{
	public static $table = 'pages';

	public function items()
	{
		return $this->has_many('Item');
	}
}