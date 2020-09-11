<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class TimingTask extends Command
{
    protected function configure() {
        $this->setName('task')
            ->addArgument('name', Argument::OPTIONAL, "your name")
            ->addOption('order', null, Option::VALUE_REQUIRED, 'order action name')
            ->addOption('activity', null, Option::VALUE_REQUIRED, 'activity action name')
            ->addOption('rebate', null, Option::VALUE_REQUIRED, 'rebate action name')
            ->addOption('remittance', null, Option::VALUE_REQUIRED, 'remittance action name')
            ->setDescription('exec timing tsk');
    }

    protected function execute(Input $input, Output $output) {
        $name = trim($input->getArgument('name'));
        if ($name !== 'zhouxing') {
            trace('getArguments : ' . json_encode($input->getArguments()) . '; getOptions： ' . json_encode($input->getOptions()), 'error');
            $output->writeln('没有执行权限');
            return;
        }

        $action = 'TimingTask/';
        if ($input->hasOption('order')) {
            $action .= 'Order/' . $input->getOption('order');
            if (!method_exists(\app\TimingTask\controller\Order::class, $input->getOption('order'))) {
                $output->writeln('没有当前方法, ' . $action);
                return;
            }
        } elseif ($input->hasOption('activity')) {
            $action .= 'Activity/' . $input->getOption('activity');
            if (!method_exists(\app\TimingTask\controller\Activity::class, $input->getOption('activity'))) {
                $output->writeln('没有当前方法, ' . $action);
                return;
            }
        } elseif ($input->hasOption('rebate')) {
            $action .= 'Rebate/' . $input->getOption('rebate');
            if (!method_exists(\app\TimingTask\controller\Rebate::class, $input->getOption('rebate'))) {
                $output->writeln('没有当前方法, ' . $action);
                return;
            }
        } elseif ($input->hasOption('remittance')) {
            $action .= 'Remittance/' . $input->getOption('remittance');
            if (!method_exists(\app\TimingTask\controller\Remittance::class, $input->getOption('remittance'))) {
                $output->writeln('没有当前方法, ' . $action);
                return;
            }
        } else {
            $output->writeln('没有可执行的操作');
            return;
        }

        $msg = action($action);
        $output->writeln($action . '：' . $msg);
    }
}