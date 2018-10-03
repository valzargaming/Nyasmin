<?php
/**
 * Yasmin
 * Copyright 2017-2018 Charlotte Dunois, All Rights Reserved
 *
 * Website: https://charuru.moe
 * License: https://github.com/CharlotteDunois/Yasmin/blob/master/LICENSE
*/

namespace CharlotteDunois\Yasmin\Interfaces;

/**
 * Something all channels implement.
 *
 * @method string  getId()                Gets the channel's ID.
 * @method string  getType()              Gets the channel type. ({@see \CharlotteDunois\Yasmin\Models\ChannelStorage::CHANNEL_TYPES})
 * @method int     getCreatedTimestamp()  Gets the timestamp of when this channel was created.
 */
interface ChannelInterface {
    /**
     * Internally patches the instance.
     */
    function _patch(array $data);
}
