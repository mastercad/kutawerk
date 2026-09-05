<?php
declare(strict_types=1);namespace App\Controller;
use App\Entity\{Event,User};use App\Repository\{CourseRepository,DepartmentRepository,EventRepository,LocationRepository};use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;use Symfony\Component\HttpFoundation\{Request,Response};use Symfony\Component\Routing\Attribute\Route;use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/editor/events',name:'editor_events')]#[IsGranted('ROLE_USER')]
final class EventEditorController extends AbstractController
{
 #[Route('',name:'',methods:['GET','POST'])]public function index(Request $request,EventRepository $events,DepartmentRepository $departments,CourseRepository $courses,LocationRepository $locations):Response
 {
  $user=$this->getUser();if(!$user instanceof User||!$user->hasPermission(User::PERMISSION_EVENTS))throw $this->createAccessDeniedException();
  if($request->isMethod('POST')){
   if(!$this->isCsrfTokenValid('event-save',(string)$request->request->get('_token')))throw $this->createAccessDeniedException();
   $event=$request->request->getInt('id')?$events->find($request->request->getInt('id')):new Event();$department=$departments->find($request->request->getInt('department'));$course=$request->request->getInt('course')?$courses->find($request->request->getInt('course')):null;if(!$event||!$department||(!in_array('ROLE_ADMIN',$user->getRoles(),true)&&!$user->getDepartments()->contains($department)))throw $this->createAccessDeniedException();
   $visibleFrom=$this->dateTime($request,'visible_from');$visibleUntil=$this->dateTime($request,'visible_until');if($visibleFrom&&$visibleUntil&&$visibleUntil<$visibleFrom){$this->addFlash('error','„Anzeigen bis“ darf nicht vor „Anzeigen ab“ liegen.');return $this->redirectToRoute('editor_events');}
   $time=trim((string)$request->request->get('time'));$event->setTitle((string)$request->request->get('title'))->setDate(new \DateTimeImmutable((string)$request->request->get('date')))->setTime($time===''?null:new \DateTimeImmutable($time))->setLocation(trim((string)$request->request->get('location'))?:null)->setOriginalDateLabel(trim((string)$request->request->get('original_date_label'))?:null)->setActive($request->request->getBoolean('active'))->setVisibleFrom($visibleFrom)->setVisibleUntil($visibleUntil)->setDescription(trim((string)$request->request->get('description'))?:null)->setLink(trim((string)$request->request->get('link'))?:null)->setDepartment($department)->setCourse($course)->setCreatedBy($event->getCreatedBy()??$user);$events->save($event);$this->addFlash('success','Der Termin wurde gespeichert.');return $this->redirectToRoute('editor_events');
  }
  return $this->render('editor/events.html.twig',['events'=>$events->findForUser($user),'departments'=>in_array('ROLE_ADMIN',$user->getRoles(),true)?$departments->findAll():$user->getDepartments(),'courses'=>$courses->findForUser($user),'locations'=>$locations->findBy(['active'=>true],['name'=>'ASC']),'today'=>new \DateTimeImmutable('today',new \DateTimeZone('Europe/Berlin'))]);
 }
 private function dateTime(Request $request,string $key):?\DateTimeImmutable{$value=trim((string)$request->request->get($key));return $value===''?null:new \DateTimeImmutable($value,new \DateTimeZone('Europe/Berlin'));}
}
