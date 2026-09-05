<?php
declare(strict_types=1);
namespace App\Controller;
use App\Entity\{Course,DocumentVersion,Location,LocationAddressVersion,TrainingSession,User,UserInvitation};
use App\Repository\{CourseRepository,DepartmentRepository,DocumentVersionRepository,LocationRepository,TrainingSessionRepository,UserRepository};
use App\Service\DocumentCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\{Request,Response};
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/editor',name:'editor_')]
#[IsGranted('ROLE_USER')]
final class EditorController extends AbstractController
{
    #[Route('',name:'dashboard',methods:['GET'])]
    public function dashboard(UserRepository $users,CourseRepository $courses,LocationRepository $locations,TrainingSessionRepository $sessions):Response{return $this->render('editor/dashboard.html.twig',['activeAccountCount'=>$users->countActiveAccounts(),'courseCount'=>$courses->count(['active'=>true]),'locationCount'=>$locations->count(['active'=>true]),'sessionCount'=>$sessions->count(['active'=>true])]);}

    #[Route('/users',name:'users',methods:['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function users(Request $request,UserRepository $users,DepartmentRepository $departments,EntityManagerInterface $em,MailerInterface $mailer,#[Autowire('%env(MAILER_FROM)%')]string $from):Response
    {
        if($request->isMethod('POST')){
            $this->csrf($request,'user-save');
            $id=$request->request->getInt('id');
            $user=$id?$users->find($id):new User();
            if(!$user instanceof User)throw $this->createNotFoundException();
            if($user===$this->getUser()&&(!$request->request->getBoolean('active')||!$request->request->getBoolean('administrator'))){$this->addFlash('error','Das eigene Administratorkonto darf nicht deaktiviert oder zum Redaktionskonto herabgestuft werden.');return $this->redirectToRoute('editor_users');}
            $accessFrom=$this->dateTime($request,'access_from');$accessUntil=$this->dateTime($request,'access_until');if($accessFrom&&$accessUntil&&$accessUntil<$accessFrom){$this->addFlash('error','„Zugang bis“ darf nicht vor „Zugang ab“ liegen.');return $this->redirectToRoute('editor_users');}
            $email=trim((string)$request->request->get('email'));$trainer=$request->request->getBoolean('trainer');$administrator=$request->request->getBoolean('administrator');$contactPerson=$request->request->getBoolean('contact_person');$permissions=(array)$request->request->all('permissions');
            if($email===''&&(!$trainer||$administrator||$contactPerson||$permissions!==[])){$this->addFlash('error','Eine E-Mail-Adresse ist für Benutzerzugänge und öffentliche Ansprechpartner erforderlich.');return $this->redirectToRoute('editor_users');}
            $user->setEmail($email!==''?$email:null)->setFirstName((string)$request->request->get('first_name'))->setLastName((string)$request->request->get('last_name'))->setTrainer($trainer)->setActive($request->request->getBoolean('active'))->setAccessFrom($accessFrom)->setAccessUntil($accessUntil)->setRoles($administrator?['ROLE_ADMIN']:($email!==''?['ROLE_EDITOR']:[]))->setPermissions($permissions)->clearDepartments();
            $user->setTrainerBio($this->nullable($request,'trainer_bio'))->setContactPerson($contactPerson)->setContactFunction($this->nullable($request,'contact_function'));
            $trainerImage=$request->files->get('trainer_image');if($trainerImage instanceof UploadedFile){$path=$this->storeTrainerImage($trainerImage);if($path===null){$this->addFlash('error','Das Trainerfoto konnte nicht verwendet werden. Bitte wählen Sie ein JPG-, PNG- oder WebP-Bild mit höchstens 5 MB.');return $this->redirectToRoute('editor_users');}$user->setTrainerImagePath($path);}
            foreach((array)$request->request->all('departments') as $departmentId){$department=$departments->find((int)$departmentId);if($department)$user->addDepartment($department);}
            $em->persist($user);$em->flush();
            if($id===0&&$email!==''){$token=bin2hex(random_bytes(32));$invite=new UserInvitation($user,hash('sha256',$token),new \DateTimeImmutable('+48 hours'));$em->persist($invite);$em->flush();$mailer->send((new TemplatedEmail())->from($from)->to($email)->subject('Einladung zur KuTaWerk-Redaktion')->htmlTemplate('emails/invitation.html.twig')->context(['user'=>$user,'url'=>$this->generateUrl('invitation_accept',['token'=>$token],0)]));}
            $this->addFlash('success',$id===0?($email!==''?'Der Benutzer wurde angelegt und eingeladen.':'Das Trainerprofil wurde ohne Benutzerzugang angelegt.'):'Die Änderungen wurden gespeichert.');return $this->redirectToRoute('editor_users');
        }
        return $this->render('editor/users.html.twig',['users'=>$users->findBy([],['lastName'=>'ASC']),'departments'=>$departments->findBy([],['name'=>'ASC']),'permissions'=>[User::PERMISSION_NEWS=>'News bearbeiten',User::PERMISSION_TRAINING=>'Trainingszeiten und Kurse bearbeiten',User::PERMISSION_EVENTS=>'Termine bearbeiten']]);
    }

    #[Route('/locations',name:'locations',methods:['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function locations(Request $request,LocationRepository $repo,TrainingSessionRepository $sessions,EntityManagerInterface $em):Response
    {
        if($request->isMethod('POST')){
            $this->csrf($request,'location-save');$location=$request->request->getInt('id')?$repo->find($request->request->getInt('id')):new Location();if(!$location)throw $this->createNotFoundException();
            $from=$this->date($request,'valid_from');$until=$this->date($request,'valid_until');if($from&&$until&&$until<$from){$this->addFlash('error','„Adresse gültig bis“ darf nicht vor „Adresse gültig ab“ liegen.');return $this->redirectToRoute('editor_locations');}
            $versionId=$request->request->getInt('address_version_id');$version=$versionId?$em->getRepository(LocationAddressVersion::class)->find($versionId):new LocationAddressVersion();if(!$version||($versionId&&$version->getLocation()!==$location))throw $this->createAccessDeniedException();
            if($location->getId()!==null){$query=$em->getRepository(LocationAddressVersion::class)->createQueryBuilder('v')->select('COUNT(v.id)')->andWhere('v.location = :location')->andWhere('(:until IS NULL OR v.validFrom IS NULL OR v.validFrom <= :until)')->andWhere('(:from IS NULL OR v.validUntil IS NULL OR v.validUntil >= :from)')->setParameter('location',$location)->setParameter('from',$from)->setParameter('until',$until);if($versionId)$query->andWhere('v.id != :versionId')->setParameter('versionId',$versionId);if((int)$query->getQuery()->getSingleScalarResult()>0){$this->addFlash('error','Dieser Adresszeitraum überschneidet sich mit einer vorhandenen Adresse. Bitte passen Sie zuerst deren Enddatum an.');return $this->redirectToRoute('editor_locations');}}
            $location->setName((string)$request->request->get('name'))->setActive($request->request->getBoolean('active'));$version->setStreet((string)$request->request->get('street'))->setPostalCode((string)$request->request->get('postal_code'))->setCity((string)$request->request->get('city'))->setNotes($this->nullable($request,'notes'))->setValidFrom($from)->setValidUntil($until);$location->addAddressVersion($version);$em->persist($location);$em->persist($version);$em->flush();$this->addFlash('success','Der Trainingsort und sein Adresszeitraum wurden gespeichert.');return $this->redirectToRoute('editor_locations');
        }
        return $this->render('editor/locations.html.twig',['locations'=>$repo->findBy([],['name'=>'ASC']),'usageCounts'=>$sessions->countActiveGroupedByLocation()]);
    }

    #[Route('/courses',name:'courses',methods:['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function courses(Request $request,CourseRepository $repo,DepartmentRepository $departments,UserRepository $users,EntityManagerInterface $em):Response
    {
        if($request->isMethod('POST')){$this->csrf($request,'course-save');$course=$request->request->getInt('id')?$repo->find($request->request->getInt('id')):new Course();$department=$departments->find($request->request->getInt('department'));if(!$course||!$department)throw $this->createNotFoundException();$validFrom=$this->date($request,'valid_from');$validUntil=$this->date($request,'valid_until');if($validFrom&&$validUntil&&$validUntil<$validFrom){$this->addFlash('error','„Angeboten bis“ darf nicht vor „Angeboten ab“ liegen.');return $this->redirectToRoute('editor_courses');}$course->setName((string)$request->request->get('name'))->setAgeGroup($this->nullable($request,'age_group'))->setDepartment($department)->setActive($request->request->getBoolean('active'))->setValidFrom($validFrom)->setValidUntil($validUntil)->clearTrainers();foreach((array)$request->request->all('trainers') as $id){$trainer=$users->find((int)$id);if($trainer)$course->addTrainer($trainer);}$em->persist($course);$em->flush();$this->addFlash('success','Der Kurs wurde gespeichert.');return $this->redirectToRoute('editor_courses');}
        return $this->render('editor/courses.html.twig',['courses'=>$repo->findBy([],['name'=>'ASC']),'departments'=>$departments->findAll(),'trainers'=>$users->findTrainers()]);
    }

    #[Route('/training-times',name:'training_times',methods:['GET','POST'])]
    public function training(Request $request,TrainingSessionRepository $repo,CourseRepository $courses,LocationRepository $locations,EntityManagerInterface $em):Response
    {
        $user=$this->getUser();if(!$user instanceof User||!$user->hasPermission(User::PERMISSION_TRAINING))throw $this->createAccessDeniedException();
        if($request->isMethod('POST')){$this->csrf($request,'training-save');$session=$request->request->getInt('id')?$repo->find($request->request->getInt('id')):new TrainingSession();$course=$courses->find($request->request->getInt('course'));$location=$locations->find($request->request->getInt('location'));if(!$session||!$course||!$location||(!in_array('ROLE_ADMIN',$user->getRoles(),true)&&!$course->getTrainers()->contains($user)))throw $this->createAccessDeniedException();$start=new \DateTimeImmutable((string)$request->request->get('starts_at'));$end=new \DateTimeImmutable((string)$request->request->get('ends_at'));if($end<=$start){$this->addFlash('error','Die Endzeit muss nach der Startzeit liegen. Es wurde nichts gespeichert.');return $this->redirectToRoute('editor_training_times');}$session->setCourse($course)->setLocation($location)->setWeekday($request->request->getInt('weekday'))->setStartsAt($start)->setEndsAt($end)->setNotes($this->nullable($request,'notes'))->setDanceStyle($this->nullable($request,'dance_style'))->setLegacyTrainerNames($this->nullable($request,'trainer_names'))->setValidFrom($this->date($request,'valid_from'))->setValidUntil($this->date($request,'valid_until'))->setActive($request->request->getBoolean('active'));if($session->getValidFrom()&&$session->getValidUntil()&&$session->getValidUntil()<$session->getValidFrom()){$this->addFlash('error','Das Enddatum darf nicht vor dem Startdatum liegen. Es wurde nichts gespeichert.');return $this->redirectToRoute('editor_training_times');}$em->persist($session);$em->flush();$this->addFlash('success','Die Trainingszeit wurde gespeichert.');return $this->redirectToRoute('editor_training_times');}
        return $this->render('editor/training.html.twig',['sessions'=>$repo->findForUser($user),'courses'=>$courses->findForUser($user),'locations'=>$locations->findBy(['active'=>true],['name'=>'ASC'])]);
    }

    #[Route('/documents',name:'documents',methods:['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function documents(Request $request,DocumentVersionRepository $repo,DocumentCatalog $catalog,EntityManagerInterface $em):Response
    {
        if($request->isMethod('POST')){
            $this->csrf($request,'document-save');
            $id=$request->request->getInt('id');$key=trim((string)$request->request->get('document_key'));$document=$id?$repo->find($id):new DocumentVersion();
            if(!$document instanceof DocumentVersion||!$catalog->has($key)||($id&&$document->getDocumentKey()!==$key))throw $this->createNotFoundException();
            $from=$this->date($request,'valid_from');$until=$this->date($request,'valid_until');
            if($from&&$until&&$until<$from){$this->addFlash('error','„Gültig bis“ darf nicht vor „Gültig ab“ liegen.');return $this->redirectToRoute('editor_documents');}
            $file=$request->files->get('document_file');
            if(!$id&&!$file instanceof UploadedFile){$this->addFlash('error','Bitte wählen Sie für die neue Dokumentversion eine PDF-Datei aus.');return $this->redirectToRoute('editor_documents');}
            if($id&&$file instanceof UploadedFile){$this->addFlash('error','Eine vorhandene Datei wird nicht überschrieben. Bitte legen Sie die neue Datei als neue Version an.');return $this->redirectToRoute('editor_documents');}
            if($file instanceof UploadedFile&&((string)$file->getMimeType()!=='application/pdf'||$file->getSize()>15_000_000)){$this->addFlash('error','Die Datei muss ein PDF mit höchstens 15 MB sein.');return $this->redirectToRoute('editor_documents');}
            $excluded=[];
            if(!$id){$from??=new \DateTimeImmutable('today',new \DateTimeZone('Europe/Berlin'));$current=$repo->findCurrent($key,$from);if($current&&(!$current->getValidFrom()||$current->getValidFrom()<$from)&&(!$current->getValidUntil()||$current->getValidUntil()>=$from)){$current->setValidUntil($from->modify('-1 day'));$excluded[]=$current->getId();}}
            else $excluded[]=$id;
            $overlap=$repo->createQueryBuilder('d')->select('COUNT(d.id)')->andWhere('d.documentKey = :key')->andWhere('(:until IS NULL OR d.validFrom IS NULL OR d.validFrom <= :until)')->andWhere('(:from IS NULL OR d.validUntil IS NULL OR d.validUntil >= :from)')->setParameter('key',$key)->setParameter('from',$from)->setParameter('until',$until);
            if($excluded)$overlap->andWhere('d.id NOT IN (:excluded)')->setParameter('excluded',$excluded);
            if((int)$overlap->getQuery()->getSingleScalarResult()>0){$this->addFlash('error','Dieser Gültigkeitszeitraum überschneidet sich mit einer vorhandenen Version. Bitte passen Sie zuerst deren Zeitraum an.');return $this->redirectToRoute('editor_documents');}
            if($file instanceof UploadedFile){$stored=$this->storeDocument($file,$key);$document->setStoredPath($stored)->setOriginalName((string)$file->getClientOriginalName())->setMimeType('application/pdf')->setFileSize((int)$file->getSize());}
            $document->setDocumentKey($key)->setValidFrom($from)->setValidUntil($until);$em->persist($document);$em->flush();$this->addFlash('success',$id?'Der Gültigkeitszeitraum wurde gespeichert.':'Die neue Dokumentversion wurde gespeichert.');return $this->redirectToRoute('editor_documents');
        }
        $versions=[];foreach(array_keys($catalog->all()) as $key)$versions[$key]=$repo->findVersions($key);
        return $this->render('editor/documents.html.twig',['catalog'=>$catalog->all(),'versions'=>$versions,'today'=>new \DateTimeImmutable('today',new \DateTimeZone('Europe/Berlin'))]);
    }

    private function csrf(Request $request,string $id):void{if(!$this->isCsrfTokenValid($id,(string)$request->request->get('_token')))throw $this->createAccessDeniedException();}
    private function nullable(Request $request,string $key):?string{$value=trim((string)$request->request->get($key));return $value===''?null:$value;}
    private function date(Request $request,string $key):?\DateTimeImmutable{$value=$this->nullable($request,$key);return $value?new \DateTimeImmutable($value):null;}
    private function dateTime(Request $request,string $key):?\DateTimeImmutable{$value=$this->nullable($request,$key);return $value?new \DateTimeImmutable($value,new \DateTimeZone('Europe/Berlin')):null;}
    private function storeTrainerImage(UploadedFile $image):?string{$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$mime=(string)$image->getMimeType();if(!isset($allowed[$mime])||$image->getSize()>5_000_000)return null;$filename=sprintf('trainer-%s-%s.%s',date('YmdHis'),bin2hex(random_bytes(5)),$allowed[$mime]);try{$image->move($this->getParameter('kernel.project_dir').'/public/uploads/trainers',$filename);}catch(\Throwable){return null;}return '/uploads/trainers/'.$filename;}
    private function storeDocument(UploadedFile $file,string $key):string{$directory=$this->getParameter('kernel.project_dir').'/public/uploads/documents';if(!is_dir($directory))mkdir($directory,0775,true);$filename=sprintf('%s-%s-%s.pdf',$key,date('YmdHis'),bin2hex(random_bytes(5)));$file->move($directory,$filename);return '/uploads/documents/'.$filename;}
}
