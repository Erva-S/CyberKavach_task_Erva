<?php
declare(strict_types=1);

namespace {
    $composerAutoload = __DIR__ . '/composer/autoload.php';
    if (is_file($composerAutoload)) {
        require_once $composerAutoload;
        return;
    }

    spl_autoload_register(static function (string $class): void {
        $prefix = 'CyberKavach\\';
        $baseDir = dirname(__DIR__) . '/src/';

        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_file($file)) {
            require_once $file;
        }
    });
}

namespace PHPMailer\PHPMailer {
    if (!class_exists(PHPMailer::class, false)) {
        class PHPMailer
        {
            public const ENCRYPTION_STARTTLS = 'tls';

            public string $Host = '';
            public bool $SMTPAuth = false;
            public string $Username = '';
            public string $Password = '';
            public string $SMTPSecure = '';
            public int $Port = 0;
            public string $Subject = '';
            public string $Body = '';
            public string $AltBody = '';

            public function __construct(bool $exceptions = false)
            {
            }

            public function isSMTP(): void
            {
            }

            public function setFrom(string $address, string $name = ''): void
            {
            }

            public function isHTML(bool $isHtml = true): void
            {
            }

            public function clearAddresses(): void
            {
            }

            public function addAddress(string $address, string $name = ''): void
            {
            }

            public function send(): bool
            {
                return false;
            }
        }
    }

    if (!class_exists(SMTP::class, false)) {
        class SMTP
        {
            public const ENCRYPTION_STARTTLS = 'tls';
        }
    }
}

namespace Twig\Loader {
    if (!class_exists(FilesystemLoader::class, false)) {
        class FilesystemLoader
        {
            private string $path;

            public function __construct(string $path)
            {
                $this->path = rtrim($path, '\\/');
            }

            public function resolveTemplate(string $template): string
            {
                $file = $this->path . DIRECTORY_SEPARATOR . $template;

                if (!is_file($file)) {
                    throw new \RuntimeException('Twig template not found: ' . $template);
                }

                return $file;
            }
        }
    }
}

namespace Twig {
    use Twig\Loader\FilesystemLoader;

    if (!class_exists(Environment::class, false)) {
        class Environment
        {
            private FilesystemLoader $loader;

            /**
             * @var array<string, mixed>
             */
            private array $options;

            public function __construct(FilesystemLoader $loader, array $options = [])
            {
                $this->loader = $loader;
                $this->options = $options;
            }

            public function render(string $template, array $data = []): string
            {
                $file = $this->loader->resolveTemplate($template);
                $html = file_get_contents($file);

                if ($html === false) {
                    throw new \RuntimeException('Unable to read Twig template: ' . $template);
                }

                foreach ($data as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                    }

                    $safe = htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $html = str_replace('{{' . $key . '}}', $safe, $html);
                }

                return preg_replace('/\{\{[a-zA-Z0-9_]+\}\}/', '', $html) ?? $html;
            }
        }
    }
}