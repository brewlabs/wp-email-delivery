<?php

function wped_get_option( $key ){
	if($key != ''){
		return get_option('wped_' . $key);
	}
}