<?php
namespace Services;

class ButtonHandlerService
{
    private $message;
    private $keyboard;
    private $user;

    public function __construct($user, $keyboard, $message)
    {
        $this->user = $user;
        $this->keyboard = $keyboard;
        $this->message = $message;
    }

    public function handle($idTelegram, $text, $history)
    {
        $routes = [
            '🏠 Main menu'              => 'mainMenu',
            '📘 Lessons'                => 'lesson',
            'ℹ️ About'                  => 'about',
            '✏️ Edit name'              => 'editName',
            '📝 Vocabulary'             => 'vocabulary',
            '💬 Phrases'                => 'phrase',
            'Next ➡️'                   =>'next',
            '⬅️ Previous'               =>'previous',
            '🔃 Refresh'                =>'refresh',
            '⬅️ Back'                   => 'back'
        ];
        
        $history = json_decode($history, true);
        $infoUser = $this->user->UserCheck($idTelegram);
        
        if($history['position']=="editName")
        {
            $data = ['name'=>$text, 'levelId'=>$infoUser['levelId']];
            $this->user->updateUserInfo($idTelegram, $data);
            $method = "back";
            $this->$method($idTelegram, $history, $method, $text, $infoUser);
            return true;
        }
        
        if(!isset($routes[$text]))
        {
            $method = $history['position'];
            
            if($method == "vocabulary" || $method == "phrase")
            {
                if(filter_var($text, FILTER_VALIDATE_INT) !== false)
                {
                    $text = $text;
                }
                else $text = "🤦";
            }
            
            else $text = "🤦";
            
            $this->$method($idTelegram, $history, $method, $text, $infoUser);
            return true;
        }
        
        $method = $routes[$text];
        $this->$method($idTelegram, $history, $method, $text, $infoUser);
        return true;
    }
    
    //mainMenu
    private function mainMenu($idTelegram, $history, $method, $text, $infoUser)
    {
        $this->user->updatePosition($idTelegram, $history, $method);
        $this->message->send($idTelegram, "$text", $this->keyboard->$method());
    }
    
    //Lessons
    private function lesson($idTelegram, $history, $method, $text, $infoUser)
    {
        $level = [
            '1' => '*A1 Begginer*',
            '2' => '*A2 Elementary*',
            '3' => '*B1 Intermediate*',
            '4' => '*B2 Upper-Intermediate*',
            '5' => '*C1 Advanced*',
            '6' => '*C2 Proficiency*'
        ];
        
        $this->user->updatePosition($idTelegram, $history, $method);
        $word = $this->user->ReadBase('mainEnglishLessons', $history['lesson']);
        $messageText = $level[$word['levelId']].
        "\n".$word['lesson'];
        $this->message->send($idTelegram, $messageText, $this->keyboard->lesson($history['lesson']));
        $folder = ['name'=>'lesson', 'id'=>$history['lesson']];
        $this->message->voice($idTelegram, $folder);
        
        if($infoUser['levelId'] != $word['levelId'])
        {
            $data = ['name'=>$infoUser['name'], 'levelId'=>$word['levelId']];
            $this->user->updateUserInfo($idTelegram, $data);
        }
    }
    
    //About
    private function about($idTelegram, $history, $method, $text, $infoUser)
    {
        $level = [
            '1' => '*A1 Begginer*',
            '2' => '*A2 Elementary*',
            '3' => '*B1 Intermediate*',
            '4' => '*B2 Upper-Intermediate*',
            '5' => '*C1 Advanced*',
            '6' => '*C2 Proficiency*'
        ];
        $this->user->updatePosition($idTelegram, $history, $method);
        $text = "Your name: " . $infoUser['name'] . "\nYour level: " . $level[$infoUser['levelId']];
        $this->message->send($idTelegram, "$text", $this->keyboard->$method());
    }
    
    //Edit name
    private function editName($idTelegram, $history, $method, $text, $infoUser)
    {
        $this->user->updatePosition($idTelegram, $history, $method);
        $this->message->send($idTelegram, "Write your name", $this->keyboard->remove());
    }
    
    //Vocabulary
    private function vocabulary($idTelegram, $history, $method, $text, $infoUser)
    {
        $this->user->updatePosition($idTelegram, $history, $method);
        if(filter_var($text, FILTER_VALIDATE_INT) !== false) $history['vocabulary']=$text;
        $word = $this->user->ReadBase('mainEnglishVocabulary', $history['vocabulary']);
        $messageText =
        "*En:* " . $word['enWord'] . " — *Spelling:* " . $word['enSpelling'] .
        "\n*Uz:* " . "||".$word['uzWord']. "||".
        "\n*Ru:* " . "||".$word['ruWord']. "||".
        "\n*Tr:* " . "||".$word['trWord']. "||";
        $this->message->send($idTelegram, $messageText, $this->keyboard->$method($history['vocabulary']));
        $folder = ['name'=>'vocabulary', 'id'=>$history['vocabulary']];
        $this->message->voice($idTelegram, $folder);
    }
    
    //Phrases
    private function phrase($idTelegram, $history, $method, $text, $infoUser)
    {
        $this->user->updatePosition($idTelegram, $history, $method);
        if(filter_var($text, FILTER_VALIDATE_INT) !== false)$history['phrase']=$text;
        $word = $this->user->ReadBase('mainEnglishPhrases', $history['phrase']);
        $messageText =
        "*En:* " . $word['enPhrase'] . " — *Spelling:* " . $word['enSpelling'] .
        "\n*Uz:* " . "||".$word['uzPhrase']."||".
        "\n*Ru:* " . "||".$word['ruPhrase']."||".
        "\n*Tr:* " . "||".$word['trPhrase']."||";
        $this->message->send($idTelegram, $messageText, $this->keyboard->$method($history['phrase']));
        $folder = ['name'=>'phrase', 'id'=>$history['phrase']];
        $this->message->voice($idTelegram, $folder);
    }
    
    //Next
    private function next($idTelegram, $history, $method, $text, $infoUser)
    {
        $position = $history['position'];
        $next = $history[$position];
        $dirId = [
            'lesson' => 'mainEnglishLessons',
            'vocabulary' => 'mainEnglishVocabulary',
            'phrase' => 'mainEnglishPhrases'
        ];
        $count = $this->user->ReadCountBase($dirId[$position]);
        if($count==$next)$next = $next-1;
        $next = $next+1;
        $this->user->updatePagination($idTelegram, $history, $position, $next);
        $user = $this->user->UserCheck($idTelegram);
        $history = json_decode($user['history'], true);
        
        $this->$position($idTelegram, $history, $position, $text, $infoUser);
    }
    
    //Previous
    private function previous($idTelegram, $history, $method, $text, $infoUser)
    {
        $position = $history['position'];
        $previous = $history[$position];
        if($previous==1)$previous=2;
        $previous = $previous-1;
        $this->user->updatePagination($idTelegram, $history, $position, $previous);
        $user = $this->user->UserCheck($idTelegram);
        $history = json_decode($user['history'], true);
        
        $this->$position($idTelegram, $history, $position, $text, $infoUser);
    }
    
    //Refresh
    private function refresh($idTelegram, $history, $method, $text, $infoUser)
    {
        $position = $history['position'];
        $index = $history[$position];
        $index = random_int(1, $index);
        $index = (int) $index;
        
        $this->$position($idTelegram, $history, $position, $index, $infoUser);
    }
    
    //Back
    private function back($idTelegram, $history, $method, $text, $infoUser)
    {
        $routesMainMenu = [
            'lesson'        => 'lesson',
            'vocabulary'    => 'vocabulary',
            'phrase'        => 'phrase',
            'about'         => 'about',
            'editName'      => 'editName',
        ];

    // Main menu ga qaytish
        if (isset($routesMainMenu[$history['position']])) {
            $this->user->updatePosition($idTelegram, $history, 'mainMenu');
            $this->message->send(
                $idTelegram,
                "🏠 Main menu: choose an option 👇",
                $this->keyboard->mainMenu()
            );
            return true;
        }
    }
}
