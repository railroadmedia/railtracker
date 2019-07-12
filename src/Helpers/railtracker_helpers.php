<?php

if (! function_exists('railtracker_session_token')) {
    /**
     * @return string|null
     */
    function railtracker_session_token()
    {
        return session()->isStarted() ? encrypt(session()->getId()) : null;
    }
}
