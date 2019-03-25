<?php

namespace Pumukit\OpencastBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class OpencastStopWorkflowCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:opencast:workflow:stop')
            ->setDescription('Stop given workflow or all finished workflows')
            ->addOption('mediaPackageId', null, InputOption::VALUE_REQUIRED, 'Set this parameter to stop workflow with given mediaPackageId')
            ->setHelp(<<<'EOT'
Command to stop workflows in Opencast Server.

Given mediaPackageId, will stop that workflow, all finished otherwise.

EOT
                      );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('logger');

        $opencastWorkflowService = $this->getContainer()->get('pumukit_opencast.workflow');
        $opencastClientService = $this->getContainer()->get('pumukit_opencast.client');
        if ($opencastClientService->getOpencastVersion() >= '2.0.0') {
            //$output->writeln('<info>Error on stopping workflows</info>');
            if ($mediaPackageId = $input->getOption('mediaPackageId')) {
                $opencastClientService->removeEvent($mediaPackageId);
                $output->writeln('<info>Removed event with id'.$mediaPackageId.'</info>');

                return 1;
            }
            $statistics = $opencastClientService->getWorkflowStatistics();
            $total = 0;
            if (isset($statistics['statistics']['total'])) {
                $total = $statistics['statistics']['total'];
            }

            if (0 == $total) {
                return null;
            }
            $workflowName = 'retract';
            $decode = $opencastClientService->getCountedWorkflowInstances('', $total, $workflowName);
            if (!isset($decode['workflows']['workflow'])) {
                //Data error
                return 0;
            }
            foreach ($decode['workflows']['workflow'] as $workflow) {
                if (!isset($workflow['mediapackage']['id'])) {
                    //Error?
                    continue;
                }
                $opencastClientService->removeEvent($workflow['mediapackage']['id']);
            }

            return 1;
        }

        $deleteArchiveMediaPackage = $this->getContainer()->getParameter('pumukit_opencast.delete_archive_mediapackage');

        if ($deleteArchiveMediaPackage) {
            $mediaPackageId = $input->getOption('mediaPackageId');
            $result = $opencastWorkflowService->stopSucceededWorkflows($mediaPackageId);
            if (!$result) {
                $output->writeln('<error>Error on stopping workflows</error>');
                $logger->error('['.__CLASS__.']('.__FUNCTION__.') Error on stopping workflows');

                return -1;
            }
            $output->writeln('<info>Successfully stopped workflows</info>');
            $logger->info('['.__CLASS__.']('.__FUNCTION__.') Successfully stopped workflows');
        } else {
            $output->writeln('<info>Not allowed to stop workflows</info>');
            $logger->warning('['.__CLASS__.']('.__FUNCTION__.') Not allowed to stop workflows');
        }

        return 1;
    }
}