<?php

class Page extends Eloquent\Model 
{
	public static $table = 'pages';

	public function items()
	{
		return $this->has_many('Item');
	}
}