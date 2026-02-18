<?php
namespace App\Presentation\Sign;

use Nette;
use Nette\Application\UI\Form;

class SignPresenter extends Nette\Application\UI\Presenter
{
    /**
     * Komponenta přihlašovacího formuláře.
     */
    protected function createComponentSignInForm(): Form
    {
        $form = new Form;
        $form->addEmail('email', 'Email:')
            ->setRequired('Zadejte prosím svůj email.');

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Zadejte prosím heslo.');

        $form->addSubmit('send', 'Přihlásit se');

        $form->onSuccess[] = function (Form $form, \stdClass $data) {
            try {
                $this->getUser()->login($data->email, $data->password);
                $this->redirect('Home:default');

            } catch (Nette\Security\AuthenticationException $e) {
                $form->addError('Nesprávný email nebo heslo.');
            }
        };

        return $form;
    }

    /**
     * Odhlášení uživatele.
     */
    public function actionOut(): void
    {
        $this->getUser()->logout();
        $this->flashMessage('Byli jste úspěšně odhlášeni.');
        $this->redirect('in');
    }
}