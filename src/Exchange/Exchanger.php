<?php

namespace Softspring\CmsTranslationPlugin\Exchange;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\File;

class Exchanger
{
    /**
     * @var ExchangerInterface[]
     */
    protected array $exchangers;

    /**
     * @param ExchangerInterface[] $exchangers
     */
    public function __construct(iterable $exchangers)
    {
        $this->exchangers = \is_array($exchangers) ? $exchangers : iterator_to_array($exchangers);
    }

    public function has(string $name): bool
    {
        return isset($this->exchangers[$name]);
    }

    public function get(string $name): ExchangerInterface
    {
        if (!$this->has($name)) {
            throw new InvalidArgumentException(sprintf('Exchanger "%s" not found. Available: "%s".', $name, get_class($this)));
        }

        return $this->exchangers[$name];
    }

    public function getImporter(File $file): ExchangerInterface
    {
        foreach ($this->exchangers as $exchanger) {
            if ($exchanger::supportsImport($file)) {
                return $exchanger;
            }
        }

        throw new InvalidArgumentException(sprintf('No exchanger found for file "%s".', $file->getFilename()));
    }
}
