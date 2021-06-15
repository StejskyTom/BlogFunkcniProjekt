<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Form;

class PostPresenter extends Nette\Application\UI\Presenter
{
    private Nette\Database\Explorer $database;

    public function __construct(Nette\Database\Explorer $database)
    {
        $this->database = $database;
    }

    public function renderShow(int $postId): void
    {
        $post = $this->database->table('posts')->get($postId);
        if (!$post){
            $this->error('Stránka nenalezena');
        }

        $this->template->post = $post;
        $this->template->comments = $post->related('comments')->order('created_at');
    }

    protected function createComponentCommentForm(): Form
    {
        $form = new Form();
        $form->addText('name', 'Jméno:')->setRequired();
        $form->addEmail('email','E-mail:');
        $form->addTextArea('content','Komentář:')->setRequired();
        $form->addSubmit('send','Publikovat:');

        //zavolání metody po úspěšném odeslání formu pro uložení
        $form->onSuccess[] = [$this, 'commentFormSucceeded'];

        return $form;
    }

    protected function createComponentPostForm(): Form
    {
        $form = new Form();
        $form->addText('title','Titulek:')->setRequired();
        $form->addTextArea('content','Obsah:')->setRequired();
        $form->addSubmit('send','Uložit a publikovat');
        $form->onSuccess[] = [$this, 'postFormSucceeded'];

        return $form;
    }

    public function postFormSucceeded(array $values): void
    {
        $post = $this->database->table('posts')->insert($values);

        $this->flashMessage('Příspěvek byl publikován.','success');
        $this->redirect('show', $post->id);
    }

    public function commentFormSucceeded(\stdClass $values): void
    {
        $postId = $this->getParameter('postId');

        $this->database->table('comments')->insert([
            'post_id' => $postId,
            'name' => $values->name,
            'email' => $values->email,
            'content' => $values->content
        ]);

        $this->flashMessage('Děkuji za komentář','success');
        $this->redirect('this');
    }
}