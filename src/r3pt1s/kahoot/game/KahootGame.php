<?php

namespace r3pt1s\kahoot\game;

use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use r3pt1s\kahoot\game\handler\KahootGameHandler;
use r3pt1s\kahoot\game\participant\GameParticipant;
use r3pt1s\kahoot\game\question\answer\QuestionAnswer;
use r3pt1s\kahoot\game\question\GameQuestion;
use r3pt1s\kahoot\game\question\type\QuizQuestion;
use r3pt1s\kahoot\game\question\type\SliderQuestion;
use r3pt1s\kahoot\game\question\type\TrueOrFalseQuestion;
use r3pt1s\kahoot\game\template\Template;
use r3pt1s\kahoot\Kahoot;

class KahootGame {

    public const MAX_PLAYERS = 20;

    private ?KahootGameHandler $gameHandler;
    /** @var array<GameParticipant> */
    private array $participants = [];

    private bool $running = false;
    private bool $isQuestioning = false;
    private ?GameQuestion $currentGameQuestion = null;
    private int $currentGameQuestionIndex = -1;
    /** @var array<QuestionAnswer> */
    private array $currentQuestionAnswers = [];
    private float $questionStart = 0.0;

    private bool $forceDoublePoints = false;
    private bool $forceEveryAnswerCorrect = false;

    public function __construct(
        private readonly int $gameId,
        private readonly string $invitationCode,
        private readonly string $host,
        private readonly Template $template,
        private readonly GameSettings $settings,
        private readonly bool $publicLobby
    ) {
        $this->gameHandler = new KahootGameHandler($this->gameId);
        if ($this->settings->isHostCanPlay()) {
            $this->addParticipant($this->getHost());
        }
    }

    ### [ GAME METHODS ]

    private function reset(): void {
        $this->gameHandler = null;
        $this->currentGameQuestion = null;
        $this->currentGameQuestionIndex = -1;
        $this->currentQuestionAnswers = [];
        $this->participants = [];
        $this->questionStart = 0.0;
    }

    private function delayTask(\Closure $closure, int $ticks): void {
        Kahoot::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask($closure), $ticks);
    }

    public function sendMessage(string $message, int $delayTicks = 0, ?\Closure $onCompletion = null): void {
        if ($delayTicks > 0) {
            Kahoot::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use($message, $onCompletion): void {
                $this->getHost()?->sendMessage(Kahoot::PREFIX . $message);
                foreach ($this->participants as $participant) {
                    $participant->getOrigin()?->sendMessage(Kahoot::PREFIX . $message);
                }
                if ($onCompletion !== null) ($onCompletion)();
            }), $delayTicks);
        } else {
            $this->getHost()?->sendMessage(Kahoot::PREFIX . $message);
            foreach ($this->participants as $participant) {
                $participant->getOrigin()?->sendMessage(Kahoot::PREFIX . $message);
            }
            if ($onCompletion !== null) ($onCompletion)();
        }
    }

    public function sendForm(\Closure $closure): void {
        foreach ($this->participants as $participant) {
            $participant->getOrigin()?->sendForm(($closure)($participant));
        }
    }

    public function closeAllForms(): void {
        $this->getHost()?->closeAllForms();
        foreach ($this->participants as $participant) {
            $participant->getOrigin()?->closeAllForms();
        }
    }

    public function startGame(): void {
        if ($this->running) return;
        $this->running = true;
        $this->sendMessage("§cThe Kahoot game has been started!");
        $this->sendMessage("§cThe chat communication has been disabled for the participants.");
        $this->tick();
    }

    protected function tick(): void {
        if (!$this->running) return;
        if (($this->currentGameQuestionIndex + 1) == count($this->template->getQuestions())) {
            $this->endGame();
            return;
        }

        $this->isQuestioning = true;
        $this->currentQuestionAnswers = [];
        $this->currentGameQuestionIndex++;
        $this->currentGameQuestion = $this->template->getQuestion($this->currentGameQuestionIndex);
        $this->sendMessage("§e" . ($this->currentGameQuestionIndex + 1) . "§8. §7Question:", 10);
        $this->sendMessage("§e" . $this->currentGameQuestion->getQuestion(), 30, function (): void {
            $this->questionStart = round(microtime(true), 1);
            $this->sendForm(fn(GameParticipant $participant) => $this->currentGameQuestion->buildForm($this, $participant->isHost()));
            $this->delayTask(function (): void {
                $this->isQuestioning = false;
                $this->closeAllForms();
                $this->sendMessage("§cTime has ended!");
                if (!$this->currentGameQuestion instanceof SliderQuestion) {
                    $this->sendMessage("§7Results§8: ", 10, function (): void {
                        if ($this->currentGameQuestion instanceof QuizQuestion) {
                            foreach ($this->currentGameQuestion->getAnswers() as $answer) {
                                $this->sendMessage("§e" . $answer . "§8: §7" . $this->getAnswerPercentage($answer) . "%");
                            }

                            $this->sendMessage("§7Correct answer" . ($this->isForceEveryAnswerCorrect() ? "s" : (count($this->currentGameQuestion->getCorrectAnswers()) > 1 ? "s" : "")) . "§8: §a" . implode("§8, §a", $this->isForceEveryAnswerCorrect() ? $this->currentGameQuestion->getAnswers() : $this->currentGameQuestion->getCorrectAnswers()));
                        } else if ($this->currentGameQuestion instanceof TrueOrFalseQuestion) {
                            foreach ([true, false] as $answer) {
                                $this->sendMessage("§e" . ($answer ? "True" : "False") . "§8: §7" . $this->getAnswerPercentage($answer) . "%");
                            }

                            $this->sendMessage("§7Correct answer§8: §a" . ($this->isForceEveryAnswerCorrect() ? "True and False" : ($this->currentGameQuestion->getCorrectAnswer() ? "True" : "False")));
                        }

                        $this->sendInfoPopUps();
                        $this->delayTask(fn() => $this->tick(), 60);
                    });
                } else {
                    $this->sendMessage("§7Correct answer§8: §a" . ($this->isForceEveryAnswerCorrect() ? "Everything" : $this->currentGameQuestion->getAnswer()), 10, function (): void {
                        $this->sendInfoPopUps();
                        $this->delayTask(fn() => $this->tick(), 20);
                    });
                }
            }, $this->currentGameQuestion->getTimeLimit() * 20);
        });
    }

    protected function endGame(): void {
        $wasRunning = $this->running;
        $this->running = false;
        $this->sendMessage("§cThe Kahoot game has ended!");
        if ($wasRunning) {
            $rankings = $this->sortPlayerRankings();
            $number1Points = isset($rankings[0]) ? ($this->getParticipant($rankings[0])?->getPoints() ?? 0) : 0;
            $number2Points = isset($rankings[1]) ? ($this->getParticipant($rankings[1])?->getPoints() ?? 0) : 0;
            $number3Points = isset($rankings[2]) ? ($this->getParticipant($rankings[2])?->getPoints() ?? 0) : 0;
            $this->sendMessage("§e#1§8: §7" . ($rankings[0] ?? "NaN") . " with §e" . $number1Points . " Points!", 10);
            $this->sendMessage("§f#2§8: §7" . ($rankings[1] ?? "NaN") . " with §e" . $number2Points . " Points!", 20);
            $this->sendMessage("§c#3§8: §7" . ($rankings[2] ?? "NaN") . " with §e" . $number3Points . " Points!", 30, function () use($rankings): void {
                foreach ($this->participants as $participant) {
                    $rank = array_search($participant->getCustomName(), $rankings) + 1;
                    $totalPoints = $participant->getPoints();
                    $popup = "§7Rank: §8#§" . match ($rank) {
                            1 => "e",
                            2 => "f",
                            3 => "c",
                            default => "6"
                        } . ($rank);
                    $popup .= "\n§7Total Points: §e" . $totalPoints . " Points";
                    $participant->getOrigin()?->sendPopup($popup);

                    $this->reset();
                    KahootGameManager::getInstance()->removeGame($this);
                }
            });
        } else {
            $this->reset();
            KahootGameManager::getInstance()->removeGame($this);
        }
    }

    ### [ CLASS METHODS ]

    private function sendInfoPopUps(): void {
        if (!$this->running) return;
        $givenPoints = $this->givePoints();
        $sortedPlayerRankings = $this->sortPlayerRankings();
        foreach ($this->participants as $participant) {
            $index = array_search($participant->getCustomName(), $sortedPlayerRankings);
            $rank = $index + 1;
            $popup = "§8+§a" . ($givenPoints[$participant->getOriginName()] ?? 0) . " Points";
            $popup .= "\n§7Rank: §8#§" . match ($rank) {
                1 => "e",
                2 => "f",
                3 => "c",
                default => "6"
            } . $rank;

            if ($rank > 1 && isset($sortedPlayerRankings[$index - 1])) {
                $diff = $this->getParticipant($sortedPlayerRankings[$index - 1])->getPoints() - $participant->getPoints();
                $popup .= "\n§e" . $diff . " Points §cbehind §e" . $sortedPlayerRankings[$index - 1] . "§8.";
            }

            $participant->getOrigin()?->sendPopup($popup);
        }

        $this->setForceEveryAnswerCorrect(false);
        $this->setForceDoublePoints(false);
    }

    private function givePoints(): array {
        if (!$this->running) return [];
        $points = [];
        foreach ($this->currentQuestionAnswers as $questionAnswer) {
            if ($questionAnswer->isCorrect()) $points[$questionAnswer->getPlayer()->getName()] = $questionAnswer->calculateAndAddPoints();
        }
        return $points;
    }

    private function sortPlayerRankings(): array {
        $sortedPlayers = [];
        foreach ($this->participants as $participant) {
            $sortedPlayers[$participant->getCustomName()] = $participant->getPoints();
        }

        arsort($sortedPlayers);
        return array_keys($sortedPlayers);
    }

    private function getAnswerPercentage(mixed $answer): float {
        if (!$this->running) return -1;
        $count = count(array_filter($this->currentQuestionAnswers, fn(QuestionAnswer $playerAnswer) => $playerAnswer->getAnswer() == $answer));
        $totalAnswers = count($this->currentQuestionAnswers);
        if ($totalAnswers == 0) return 0.0;
        return $count / $totalAnswers * 100;
    }

    public function addAnswer(Player $player, mixed $answer): void {
        if (!$this->running) return;
        $this->currentQuestionAnswers[$player->getName()] = new QuestionAnswer($player, $this->currentGameQuestion, $answer, round(microtime(true), 1));
    }

    public function addParticipant(Player $player, ?string $customName = null): void {
        $this->participants[$player->getName()] = new GameParticipant($player->getName(), $customName ?? $player->getName(), $player->getName() == $this->host);
    }

    public function removeParticipant(Player $player): void {
        if (isset($this->participants[$player->getName()])) unset($this->participants[$player->getName()]);
        if (isset($this->currentQuestionAnswers[$player->getName()])) unset($this->currentQuestionAnswers[$player->getName()]);

        if ($this->host == $player->getName()) {
            $this->sendMessage("§cThe host has left the game. Cancelling the game...");
            $this->endGame();
        }
    }

    public function isForceDoublePoints(): bool {
        return $this->forceDoublePoints;
    }

    public function setForceDoublePoints(bool $forceDoublePoints): void {
        $this->forceDoublePoints = $forceDoublePoints;
    }

    public function isForceEveryAnswerCorrect(): bool {
        return $this->forceEveryAnswerCorrect;
    }

    public function setForceEveryAnswerCorrect(bool $forceEveryAnswerCorrect): void {
        $this->forceEveryAnswerCorrect = $forceEveryAnswerCorrect;
    }

    public function getCurrentGameQuestion(): ?GameQuestion {
        return $this->currentGameQuestion;
    }

    public function getCurrentGameQuestionIndex(): int {
        return $this->currentGameQuestionIndex;
    }

    public function getCurrentQuestionAnswers(): array {
        return $this->currentQuestionAnswers;
    }

    public function getQuestionStart(): float {
        return $this->questionStart;
    }

    public function getGameHandler(): KahootGameHandler {
        return $this->gameHandler;
    }

    public function getParticipant(Player|string $name): ?GameParticipant {
        $name = $name instanceof Player ? $name->getName() : $name;
        if (isset($this->participants[$name])) return $this->participants[$name];

        foreach ($this->participants as $participant) {
            if ($participant->getCustomName() == $name) return $participant;
        }

        return null;
    }

    public function isParticipant(Player|string $name): bool {
        $name = $name instanceof Player ? $name->getName() : $name;
        if (isset($this->participants[$name])) return true;

        foreach ($this->participants as $participant) {
            if ($participant->getCustomName() == $name) return true;
        }

        return false;
    }

    public function getParticipants(): array {
        return $this->participants;
    }

    public function getGameId(): int {
        return $this->gameId;
    }

    public function getInvitationCode(): string {
        return $this->invitationCode;
    }

    public function getHostName(): string {
        return $this->host;
    }

    public function getHost(): ?Player {
        return Server::getInstance()->getPlayerExact($this->host);
    }

    public function getTemplate(): Template {
        return $this->template;
    }

    public function getSettings(): GameSettings {
        return $this->settings;
    }

    public function isPublicLobby(): bool {
        return $this->publicLobby;
    }

    public function isRunning(): bool {
        return $this->running;
    }

    public function isQuestioning(): bool {
        return $this->isQuestioning;
    }
}