<?php

namespace  Murdej\DataMapper;

class Convention
{
	public static function deriveTableNameFromClass($ns, $scn)
	{
		/*$p = strrpos($cn, '\\');
		if ($p < 0) $p = 0;
		else $p++;
		return lcfirst(substr($cn, $p));*/
		return lcfirst($scn);
	}
	
	public static function autoIncrement($ci)
	{
		$ci->type = 'int';
		$ci->primary = true;
		$ci->autoIncrement = true;
		$ci->forInsert = false;
	}
}
