# PMMPKahoot [![](https://poggit.pmmp.io/shield.dl/PMMPKahoot)](https://poggit.pmmp.io/p/PMMPKahoot) [![](https://poggit.pmmp.io/shield.dl.total/PMMPKahoot)](https://poggit.pmmp.io/p/PMMPKahoot)

A Kahoot-based game for your PocketMineMP server.

## Features
- **Create your own** game templates to play with
- **Public and private** lobbies to play with the whole server
- **Play with others**, even as the host of the game

## Commands
| Usage        | Description             | Permission    |
|--------------|-------------------------|---------------|
| /kahoot      | The main Kahoot command | No Permission |
| /kahootleave | Leave your current game | No Permission |

## Configuration
```yml
public-lobbies:
  # permissionToCreate: none
  permissionToCreate: kahoot.game.public_lobby # Type "none" if your players don't need a permission to create public Kahoot games.

create-templates:
  # permissionToCreate: none
  permissionToCreate: kahoot.template.create # Type "none" if your players don't need a permission to create public Kahoot games.
```
