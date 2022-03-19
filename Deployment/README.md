# Github webhook deployment

Enables deployment process using Github webhooks

## Configuration
### Routes
Import routing resources from the bundle routing file. In the service routes configuration file add:
```
survival_kit:
    resource: "@SurvivalKitBundle/config/routes.yaml"
```

### Config options
```
survival_kit:
    deployment:
        git_remote : [remote]                           // optional, default: origin
        git_base_branch : [base_branch]                 // optional, default: master
        secret_token: [github_webhook_secret_token]     // required if webhook secret is set
```

## Usage
Bundle provides an endpoint `/deployment/github-webhook` to use along with the Github webhooks.
- configure Github webhook to send the `pull_request` event to this endpoint
- once pull request will be merged deployment process starts

### Deployment commands
All available commands can be found [here](DeploymentCommand.php).
By default following commands are run:
- gitPull
- symfonyClearCache
- opcacheReset
- composerDumpAutoload
- composerDumpEnv

### Labels
Depending on the labels set on the pull request some commands are run or not.
Default supported labels:
- composer-install
- doctrine-migration

## Customizing deployment
In some services you need to run extra deployment commands in a specific order.
During deployment, a [GithubDeploymentEvent](../Event/GithubDeploymentEvent.php) is dispatched.
The following information is available in the event class:
```
::getPullRequest()
    Returns the github pull_request event information.
::getDeploymentService()
    Returns the service used for the deployment.
```

### Create DeploymentService
Custom deployment service must implement `Leadin\SurvivalKitBundle\Deployment\Github\IGithubDeploymentService` interface.
If You want just to customize deployment commands You can extend `Leadin\SurvivalKitBundle\Deployment\Github\GithubDeploymentService` and override `::executeDeploymentCommands` method.
```
use Leadin\SurvivalKitBundle\Deployment\Github\GithubDeploymentService as SSKGithubDeploymentService;
use Leadin\SurvivalKitBundle\Deployment\Github\PullRequest;

class CustomDeploymentService extends SSKGithubDeploymentService
{
    protected function executeDeploymentCommands(PullRequest $pullRequest): void
    {
        // ... add custom logic
    }
}
```

If You need to use custom `DeploymentCommand` in the service, it can be injected with the setter, in the `service.yml`
```
    Namespace\CustomDeploymentService:
        calls:
            - setDeploymentCommand: ['@Namespace\CustomDeploymentCommand']
```

### Create DeploymentCommand
When additional commands required. It must extend default `DeploymentCommand`
```
use Leadin\SurvivalKitBundle\Deployment\DeploymentCommand as SSKDeploymentCommand;

class CustomDeploymentCommand extends SSKDeploymentCommand
{
    public function customCommand(): void
    {
        // ... add custom logic
    }
}
```

### Create PullRequest
When additional PR labels required. It must extend default `PullRequest`
```
use Leadin\SurvivalKitBundle\Deployment\Github\PullRequest as SSKPullRequest;

class CustomPullRequest extends SSKPullRequest
{
    public function hasCustomLabel(): bool
    {
        // ... add custom logic
    }
}
```

### Register an event subscriber
```
<?php
use Namespace\CustomDeploymentService;
use Namespace\CustomPullRequest;
use Leadin\SurvivalKitBundle\Event\GithubDeploymentEvent;
use Leadin\SurvivalKitBundle\Event\SurvivalKitEvents;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeploymentSubscriber implements EventSubscriberInterface
{
    private CustomDeploymentService $customDeploymentService;

    public function __construct(CustomDeploymentService $customDeploymentService)
    {
        $this->customDeploymentService = $customDeploymentService;
    }

    public static function getSubscribedEvents()
    {
        return [
            SurvivalKitEvents::GITHUB_DEPLOYMENT => 'onDeployment',
        ];
    }

    public function onDeployment(GithubDeploymentEvent $event)
    {
        $pullRequest = $event->getPullRequest();
        $event->setPullRequest(new CustomPullRequest($pullRequest->getAction(), $pullRequest->getData()));
        $event->setDeploymentService($this->customDeploymentService);
    }
}
```
