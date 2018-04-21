<?php

namespace WHMCS\Module\Blazing\Export\Common\Traits;

use Symfony\Component\Console\Output\OutputInterface;

trait DebuggerTrait
{
    /**
     * @var OutputInterface
     */
    protected $output;

    public function setConsoleOutput(OutputInterface $output)
    {
        $this->output = $output;

        return $this;
    }

    public function getConsoleOutput()
    {
        return $this->output;
    }

    public function pullPipesFrom($object)
    {
        if (!in_array(DebuggerTrait::class, class_uses(get_class($object)))) {
            throw new \RuntimeException(
                'Object should be inherited from ' . self::class . ', ' .
                get_class($object) . ' passed'
            );
        }
        /** @var DebuggerTrait $object */

        if ($object->getConsoleOutput()) {
            $this->setConsoleOutput($object->getConsoleOutput());
        }
    }

    protected function debug($text, array $context = [])
    {
        if ($this->output) {
            $this->output->writeln($text . ' ' . ($context ? json_encode($context) : ''));
        }
        else {
            echo $text . ' ' . ($context ? json_encode($context) : '') . PHP_EOL;
        }
    }
}