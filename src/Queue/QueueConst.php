<?php

namespace Kalmyk\Queue;

interface QueueConst
{
    // header TAGs
    const PKG_CMD           = '^';    // command name
    const PKG_ID            = '#';    // client generated requestId, server response about the command will have the number

    const PKG_STACK         = 'S';    // scenario stack: +!~ => array(PKG_...)
    const PKG_QUEUE         = 'q';    // qname in SUB, UNSUB, CALL, READ
    const PKG_CHANEL        = 'L';    // qChanel in SUB, UNSUB, CALL, READ
    // header PAGE stuff
    const PKG_SEGMENT       = 'E';    // current generated messages id segment
    const PKG_NEW_SEGMENT   = 'NS';   // new generator segment label
    const PKG_GEN_ID        = 'G';    // generated id for the PUSH messages
    // header KEEP/READ stuff
    const PKG_QUORUM        = 'u';    // READ quorum, count of workers to start processing 
    // header SETTLE stuff
    const PKG_CLIENT        = 'C';    // filled by queue, ID of the client who sent CALL or KEEP, SETTLE will be delivered to it
    const PKG_CID           = 'O';    // origin requestId, 
    const PKG_RESPONSE      = 'R';    // SETTLE stuff, response style to delivery, lookup to RESP_*

    const PKG_LEVEL         = 'V';    // cluster mode, repeater count

    // header PKG_CMD tag content   I immediate S stack, E with emit
    const CMD_ECHO      = 'ECHO';       // I  echo the message content
    const CMD_SUB       = 'SUB';        // SE subscribe for call and publish messages
    const CMD_UNSUB     = 'UNSUB';      // I  unsubscribe from call
    const CMD_TRACE     = 'TRACE';      // SE trace the PUSH messages
    const CMD_UNTRACE   = 'UNTRACE';    // I  do not trace messages any more
    const CMD_MARKER    = 'MARKER';     // IE setup PUSH messages marker
    const CMD_POP       = 'POP';        // I  worker ask for tasks on subscribed queues
    const CMD_UNPOP     = 'UNPOP';      // I  worker dont wait to do any tasks any more
    const CMD_PUB       = 'PUB';        // S  notify message to all connected workers on Queue/Chanel, if no users connected the message disapears
    const CMD_CALL      = 'CALL';       // SE dispatch message to one free worker, and send the worker response to the client
    const CMD_PUSH      = 'PUSH';       // S  client asks send the message to all storages to keep, client receives the maker
    const CMD_SETTLE    = 'SETTLE';     // I  worker response about message/task result (CALL/PUSH/PUB)
    const CMD_STREAM    = '~';          // I  worker streaming request (PUB/CALL/PUSH)

    const CMD_PEAR      = 'PEAR';

    // header PKG_RESPONSE tag content
    const RESP_OK      = '+';
    const RESP_ERROR   = '!';
    const RESP_EMIT    = '~';

    // body response
    const RESP_SEGMENT      = 'segment';
    const RESP_CURRENT_ID   = 'current_id';
    const RESP_QUORUM       = 'quorum';
    const RESP_SEND         = 'send';

    // body queue error array
    const RESP_ERROR_CODE = 'code';
    const RESP_ERROR_MSG  = 'message';

    // body queue error codes
    const ERROR_UNKNOWN_FUNCTION            = 100;
    const ERROR_NO_QUEUE_FOUND              = 101;
    const ERROR_ALREADY_SUBSCRIBED          = 102;
    const ERROR_SETTLE_NOT_FOUND            = 103;
    const ERROR_MARK_GENERATOR_NOT_FOUND    = 104;
    const ERROR_INCORRECT_MARKER_SEGMENT    = 105;
    const ERROR_NO_QUORUM_TO_PUSH_MESSAGE   = 106;
    const ERROR_HEADER_IS_NOT_COMPLETED     = 107;
    const ERROR_ALREADY_QUEUED              = 108;
}

