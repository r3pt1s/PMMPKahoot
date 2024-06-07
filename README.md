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


## Permissions
| Description           | Permission                     |
|-----------------------|--------------------------------|
| Create public lobbies | pmmpkahoot.public_lobby.create |
| Create templates      | pmmpkahoot.template.create     |

## Configuration
```yml
public-lobbies:
  needPermToCreate: true # Type "false" if your players don't need a permission to create public Kahoot games.

create-templates:
  needPermToCreate: true # Type "false" if your players don't need a permission to create templates.
```
