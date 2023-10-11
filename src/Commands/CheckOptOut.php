<?php

namespace SpringfieldClinic\MailgunTools\Commands;

use Illuminate\Console\Command;
use Mailgun\Mailgun;
use Mailgun\Exception\HttpClientException;

class CheckOptOut extends Command
{
    private $domain;
    private $mgClient;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgun:check-optout {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check an email for any optout and optionally remove it.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email') ?? $this->ask('What email address would you like to check?');

        if (empty($email)) {
            $this->error('No email address provided.');
            return 1;
        }

        $this->mgClient = Mailgun::create(config('mailgun-tools.key'));

        $this->domain = $this->choice(
            'What domain(s) do you want to check?',
            $this->getDomains(),
            0,
        );
        
        if ($this->domain === 'All Domains') {
            foreach ($this->getDomains() as $domain) {
                if ($domain === 'Cancel' || $domain === 'All Domains') {
                    continue;
                }

                $this->domain = $domain;
                $this->checkBounces($email);
                $this->checkUnsubscribes($email);
                $this->line('');
            }
        } else {
            $this->checkBounces($email);
            $this->checkUnsubscribes($email);
        }
    }

    private function getDomains()
    {
        $response = $this->mgClient->domains()->index();
        
        $domains = [];
        foreach ($response->getDomains() as $domain) {
            if (substr($domain->getName(), 0, 7) === 'sandbox') {
                continue;
            }

            $domains[] = $domain->getName();
        }
        sort($domains);

        return array_merge(['All Domains'], $domains);
    }

    private function checkBounces($email)
    {
        try {
            $this->mgClient->suppressions()->bounces()->show($this->domain, $email);
            $this->info('Bounce(s) on domain ('.$this->domain.')');

            if ($this->confirm('Do you wish to delete the bounce(s) on domain ('.$this->domain.')?', false)) {
                $this->deleteSuppression($email, 'bounces');
            }
        } catch (HttpClientException $e) {
            $this->error('No bounce(s) on domain ('.$this->domain.')');
        }
    }

    private function checkUnsubscribes($email)
    {
        try {
            $this->mgClient->suppressions()->unsubscribes()->show($this->domain, $email);
            $this->info('Unsubscribe(s) on domain ('.$this->domain.')');

            if ($this->confirm('Do you wish to delete the unsubscribes(s) on domain ('.$this->domain.')?', false)) {
                $this->deleteSuppression($email, 'unsubscribes');
            }
        } catch (HttpClientException $e) {
            $this->error('No unsubscribe(s) on domain ('.$this->domain.')');
        }
    }

    private function deleteSuppression($email, $type)
    {
        try {
            $this->mgClient->suppressions()->$type()->delete($this->domain, $email);
            $this->info('Deleted ' . $type . ' for: ' . $email);
        } catch (HttpClientException $e) {
            $this->error('No ' . $type . ' found for: ' . $email);
        }
    }
}
