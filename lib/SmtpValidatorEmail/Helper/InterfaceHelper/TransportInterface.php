<?php

namespace SmtpValidatorEmail\Helper\InterfaceHelper;

interface TransportInterface {

    public function connect($mxs);

    public function reconnect($from);

    public function disconnect();


}