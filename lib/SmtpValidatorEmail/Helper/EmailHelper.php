<?php

namespace SmtpValidatorEmail\Helper;


class EmailHelper {

    public static function sortEmailsByDomain(array $emails){

        usort($emails,function($a, $b) {
            $domainA = substr(strrchr($a, "@"), 1);
            $domainB = substr(strrchr($b, "@"), 1);

            if ($domainA == $domainB) {
                return 0;
            }
            return ($domainA < $domainB) ? -1 : 1;
        });

        return $emails;
    }

    public static function getGroupedEmails(array $emails){
        $emails = self::sortEmailsByDomain($emails);
        $groupedEmails = array();

        for($i = 0 ; $i<count($emails); $i++) {
            $groupedEmails[substr(strrchr($emails[$i], "@"), 1)][]= $emails[$i];
        }

        return $groupedEmails;
    }
}