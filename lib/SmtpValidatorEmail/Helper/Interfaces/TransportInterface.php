<?php

namespace SmtpValidatorEmail\Helper\Interfaces;

interface TransportInterface {

    public function connect($mxs);

    public function reconnect($from);

    public function disconnect();


}