<?php

namespace Nova\Mail;

use Nova\Mail\Transport\LogTransport;
use Nova\Mail\Transport\MailgunTransport;
use Nova\Mail\Transport\MandrillTransport;
use Nova\Support\Arr;
use Nova\Support\ServiceProvider;

use Swift_Mailer;

use Swift_SmtpTransport as SmtpTransport;
use Swift_MailTransport as MailTransport;
use Swift_SendmailTransport as SendmailTransport;

use Swift_FileSpool as FileSpool;
use Swift_SpoolTransport as SpoolTransport;


class MailServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerSwiftMailers();

        $this->app->bindShared('mailer', function ($app)
        {
            // Once we have create the mailer instance, we will set a container instance
            // on the mailer. This allows us to resolve mailer classes via containers
            // for maximum testability on said classes instead of passing Closures.
            $mailer = new Mailer(
                $app['view'], $app['swift.mailer'], $app['swift.mailer.spool'], $app['events']
            );

            $this->setMailerDependencies($mailer, $app);

            // If a "from" address is set, we will set it on the mailer so that all mail
            // messages sent by the applications will utilize the same "from" address
            // on each one, which makes the developer's life a lot more convenient.
            $from = $app['config']['mail.from'];

            if (is_array($from) && isset($from['address'])) {
                $mailer->alwaysFrom($from['address'], $from['name']);
            }

            // Here we will determine if the mailer should be in "pretend" mode for this
            // environment, which will simply write out e-mail to the logs instead of
            // sending it over the web, which is useful for local dev environments.
            $pretend = $app['config']->get('mail.pretend', false);

            $mailer->pretend($pretend);

            return $mailer;
        });

        $this->registerCommands();
    }

    public function registerCommands()
    {
        $this->app->bindShared('command.mailer.spool.flush', function ($app)
        {
            return new Console\FlushSpoolCommand($app['swift.transport'], $app['swift.transport.spool'], $app['events']);
        });

        $this->commands('command.mailer.spool.flush');
    }

    /**
     * Set a few dependencies on the mailer instance.
     *
     * @param  \Nova\Mail\Mailer  $mailer
     * @param  \Nova\Foundation\Application  $app
     * @return void
     */
    protected function setMailerDependencies($mailer, $app)
    {
        $mailer->setContainer($app);

        if ($app->bound('log')) {
            $mailer->setLogger($app['log']);
        }
    }

    /**
     * Register the Swift Mailer instance.
     *
     * @return void
     */
    public function registerSwiftMailers()
    {
        $config = $this->app['config']->get('mail');

        // Register the Swift Transports.
        $this->registerSwiftTransport($config);

        $this->registerSpoolTransport($config['spool']);

        // Once we have the transporter registered, we will register the actual Swift
        // mailer instance, passing in the transport instances, which allows us to
        // override this transporter instances during app start-up if necessary.
        $this->app['swift.mailer'] = $this->app->share(function ($app)
        {
            return new Swift_Mailer($app['swift.transport']);
        });

        $this->app['swift.mailer.spool'] = $this->app->share(function ($app)
        {
            return new Swift_Mailer($app['swift.transport.spool']);
        });
    }

    /**
     * Register the Swift Transport instance.
     *
     * @param  array  $config
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function registerSwiftTransport($config)
    {
        $driver = $config['driver'];

        switch ($driver) {
            case 'smtp':
                return $this->registerSmtpTransport($config);

            case 'sendmail':
                return $this->registerSendmailTransport($config);

            case 'mail':
                return $this->registerMailTransport($config);

            case 'mailgun':
                return $this->registerMailgunTransport($config);

            case 'mandrill':
                return $this->registerMandrillTransport($config);

            case 'log':
                return $this->registerLogTransport($config);

            default:
                throw new \InvalidArgumentException('Invalid mail driver.');
        }
    }

    /**
     * Register the SMTP Swift Transport instance.
     *
     * @param  array  $config
     * @return void
     */
    protected function registerSmtpTransport($config)
    {
        $this->app['swift.transport'] = $this->app->share(function ($app) use ($config)
        {
            extract($config);

            // The Swift SMTP transport instance will allow us to use any SMTP backend
            // for delivering mail such as Sendgrid, Amazon SES, or a custom server
            // a developer has available. We will just pass this configured host.
            $transport = SmtpTransport::newInstance($host, $port);

            if (isset($encryption)) {
                $transport->setEncryption($encryption);
            }

            // Once we have the transport we will check for the presence of a username
            // and password. If we have it we will set the credentials on the Swift
            // transporter instance so that we'll properly authenticate delivery.
            if (isset($username)) {
                $transport->setUsername($username);

                $transport->setPassword($password);
            }

            return $transport;
        });
    }

    /**
     * Register the Sendmail Swift Transport instance.
     *
     * @param  array  $config
     * @return void
     */
    protected function registerSendmailTransport($config)
    {
        $this->app['swift.transport'] = $this->app->share(function ($app) use ($config)
        {
            return SendmailTransport::newInstance($config['sendmail']);
        });
    }

    /**
     * Register the Mail Swift Transport instance.
     *
     * @param  array  $config
     * @return void
     */
    protected function registerMailTransport($config)
    {
        $this->app['swift.transport'] = $this->app->share(function ()
        {
            return MailTransport::newInstance();
        });
    }

    /**
     * Register the Mailgun Swift Transport instance.
     *
     * @param  array  $config
     * @return void
     */
    protected function registerMailgunTransport($config)
    {
        $mailgun = $this->app['config']->get('services.mailgun', array());

        $this->app['swift.transport'] = $this->app->share(function () use ($mailgun)
        {
            return new MailgunTransport($mailgun['secret'], $mailgun['domain']);
        });
    }

    /**
     * Register the Mandrill Swift Transport instance.
     *
     * @param  array  $config
     * @return void
     */
    protected function registerMandrillTransport($config)
    {
        $mandrill = $this->app['config']->get('services.mandrill', array());

        $this->app['swift.transport'] = $this->app->share(function () use ($mandrill)
        {
            return new MandrillTransport($mandrill['secret']);
        });
    }

    /**
     * Register the "Log" Swift Transport instance.
     *
     * @param  array  $config
     * @return void
     */
    protected function registerLogTransport($config)
    {
        $this->app['swift.transport'] = $this->app->share(function ($app)
        {
            return new LogTransport($app->make('Psr\Log\LoggerInterface'));
        });
    }

    /**
     * Register the Spool Swift Transport instance.
     *
     * @param  array  $config
     * @return void
     */
    protected function registerSpoolTransport($config)
    {
        $this->app['swift.transport.spool'] = $this->app->share(function ($app) use ($config)
        {
            extract($config);

            // Create a new File Spool instance.
            $spool = new FileSpool($files);

            $spool->setMessageLimit($messageLimit);
            $spool->setTimeLimit($timeLimit);
            $spool->setRetryLimit($retryLimit);

            return SpoolTransport::newInstance($spool);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('mailer', 'swift.mailer', 'swift.mailer.spool', 'swift.transport', 'swift.transport.spool');
    }

}
