<?php

namespace r3pt1s\kahoot\game\question;

enum GameQuestionType: string {

    case QUIZ = "quiz";
    case TRUE_OR_FALSE = "true_or_false";
    case SLIDER = "slider";
}