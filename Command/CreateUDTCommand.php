<?php

namespace W3com\BoomBundle\Command;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use W3com\BoomBundle\Generator\Model\Entity;
use W3com\BoomBundle\HanaEntity\UserFieldsMD;
use W3com\BoomBundle\HanaEntity\UserTablesMD;
use W3com\BoomBundle\Service\BoomManager;

class CreateUDTCommand extends Command
{
    private $manager;

    public function __construct(BoomManager $manager)
    {
        $this->manager = $manager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('boom:create-udt')
            ->setDescription('Create UDT in SAP');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        AnnotationRegistry::registerLoader('class_exists');

        $io = new SymfonyStyle($input, $output);

        $io->title("UDT Creation command");

        $entity = new Entity();
        
        $udt = new UserTablesMD();

        $udtName = $io->ask("What is your UDT name ?");
        $udtDescr = $io->ask("Give a description");

        $udtName = strpos(strtoupper($udtName), 'W3C_') === false ? 'W3C_' . $udtName : $udtName;
        $udt->setTableName($udtName);
        $udt->setTableDescription($udtDescr);
        $udt->setTableType(UserTablesMD::TABLE_TYPE_OBJECT);
        $udt->setArchivable(UserTablesMD::ARCHIVABLE_NO);

        $udtRepo = $this->manager->getRepository('UserTablesMD');

        try {
            $udtRepo->add($udt);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        $fieldsName = ['Code', 'Name'];

        $io->section('Fields of UDT');

        $io->listing($fieldsName);

        $addingField = $io->confirm("Want you add a field to your UDT ?");

        $prefix = '';

        if ($addingField) {
            $addingPrefix = $io->confirm("Want you set a prefix to your fields ?");
            if ($addingPrefix) {
                $prefix = $io->ask('Prefix', 'W3C_');

                $io->success('Prefix ' . $prefix . ' confirmed !');
            }
        }

        $fieldId = 0;

        //TODO Default value, et Mandatory
        //TODO Gestion des subtypes, 15 choix possibles

        while ($addingField) {
            $udf = new UserFieldsMD();

            $udfTable = '@' . strtoupper($udtName);

            $udfName = $io->ask("What is the name of the field ?");

            //TODO limite de 8 char au nom, + uppercase... Tester les accents et char spéciaux

            $udfName = $prefix . $udfName;

            $udfName = 'W3C_' . $udfName;

            $udfDescr = $io->ask("Give a description");

            $udfType = $io->choice("What is the type of the field ?", [
                'Alpha',
                'Date',
                'Numeric',
                'Float'
            ]);

            switch ($udfType) {
                case 'Alpha':
                    $udfType = UserFieldsMD::TYPE_ALPHA;
                    break;
                case 'Numeric':
                    $udfType = UserFieldsMD::TYPE_NUMERIC;
                    break;
                case 'Float':
                    $udfType = UserFieldsMD::TYPE_FLOAT;
                    break;
                case 'Date':
                    $udfType = UserFieldsMD::TYPE_DATE;
                    break;
            }

            if (($udfType === UserFieldsMD::TYPE_NUMERIC) || ($udfType === UserFieldsMD::TYPE_ALPHA)) {
                $size = $io->ask('Size of the field', 10, function ($number) {
                    if (!is_numeric($number)) {
                        throw new \RuntimeException('You must type a number.');
                    }

                    return (int) $number;
                });
                $udf->setSize($size);
            }

            $udf->setName($udfName);
            $udf->setTableName($udfTable);
            $udf->setType($udfType);
            $udf->setDescription($udfDescr);
            $udf->setFieldID($fieldId);

            try {
                $udfRepo = $this->manager->getRepository('UserFieldsMD');

                $udfRepo->add($udf);

                $fieldsName[] = $udfName;

                $fieldId++;
            } catch (\Exception $e) {
                $io->error($e->getMessage());
                sleep(1);
            }

            $io->section('Fields of UDT');

            $io->listing($fieldsName);

            $addingField = $io->confirm("Want you add a field to your UDT ?");
        }
    }
}