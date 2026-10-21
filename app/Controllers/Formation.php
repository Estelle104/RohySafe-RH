<?php

namespace App\Controllers;

class Formation extends BaseController
{
   public function getAll()
   {
       $model = new \App\Models\FormationModel();
       $data = $model->findAll();
       
       return $this->response->setJSON($data);
   }
    public function index()
    {
        return view('welcome');
    }

    public function create()
    {
        $model = new \App\Models\FormationModel();
        $data = [
            'titre' => $this->request->getPost('titre'),
            'description' => $this->request->getPost('description'),
            'niveau' => $this->request->getPost('niveau'),
        ];
        $model->insert($data);
        return $this->response->setJSON(['message' => 'Formation created successfully']);
    }

    public function formationForm()
    {
        return view('form');
    }


}