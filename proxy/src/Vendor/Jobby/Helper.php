<?php

namespace Vendor\Jobby;

use Jobby\Helper as BaseHelper;

class Helper extends BaseHelper
{
    public function getLockLifetime($lockFile)
    {
        if (!file_exists($lockFile)) {
            return 0;
        }

        $pid = file_get_contents($lockFile);
        if (empty($pid)) {
            return 0;
        }

        if (self::UNIX == $this->getPlatform()) {
            if (!posix_kill(intval($pid), 0)) {
                return 0;
            }
        }
        else {
            // Fallback
            $fh = fopen($lockFile, 'r+');
            if (flock($fh, LOCK_EX | LOCK_NB)) {
                fclose($fh);
                return 0;
            }
        }

        $stat = stat($lockFile);

        return (time() - $stat["mtime"]);
    }

    public function getLockPid($lockFile)
    {
        if (!file_exists($lockFile)) {
            return false;
        }

        return ($pid = file_get_contents($lockFile)) ? $pid : file_get_contents($lockFile);
    }

    public function killLockedProcess($lockFile)
    {
        // Not implemented on windows
        if (self::WINDOWS == $this->getPlatform()) {
            return false;
        }

        if ($this->getLockLifetime($lockFile)) {
            $pid = $this->getLockPid($lockFile);
            if ($pid) {
                posix_kill($pid, SIGKILL);
                sleep(1);
            }

            return 0 == $this->getLockLifetime($lockFile);
        }

        return false;
    }
}
